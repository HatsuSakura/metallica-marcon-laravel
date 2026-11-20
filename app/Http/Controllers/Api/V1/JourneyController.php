<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Journey;
use App\Services\JourneyPlanning\PlanningInput;
use App\Services\Planning\JourneyWriter;
use App\Services\Planning\PlanningGuard;
use App\Support\AgentApi\Representation;
use Illuminate\Http\Request;

class JourneyController extends Controller
{
    public function __construct(private Representation $view, private JourneyWriter $writer) {}

    public function index(Request $request)
    {
        $f = $request->validate(['status' => 'nullable|in:creato,attivo,eseguito,chiuso', 'per_page' => 'nullable|integer|min:1|max:100']);

        return Journey::query()->when(isset($f['status']), fn ($q) => $q->where('status', $f['status']))->orderByDesc('id')->paginate($f['per_page'] ?? 25)->through(fn ($j) => $this->view->journey($j));
    }

    public function show(Journey $journey)
    {
        $data = $this->view->journey($journey);

        return response()->json(['data' => $data])->header('ETag', '"'.$data['version'].'"');
    }

    public function store(Request $request)
    {
        $input = $this->input($request);
        $data = $this->view->journey($this->writer->create($input));

        return response()->json(['data' => $data], 201)->header('ETag', '"'.$data['version'].'"');
    }

    public function update(Request $request, Journey $journey)
    {
        $journey = Journey::whereKey($journey->id)->lockForUpdate()->firstOrFail();
        app(PlanningGuard::class)->journey($journey);
        $current = $this->view->journey($journey);
        $this->view->checkVersion($request, $current);
        $input = array_replace(app(PlanningInput::class)->current($journey), $request->all());
        $synthetic = Request::create('/', 'POST', $input);
        $data = $this->view->journey($this->writer->update($journey, $this->input($synthetic))->refresh());

        return response()->json(['data' => $data])->header('ETag', '"'.$data['version'].'"');
    }

    private function input(Request $request): array
    {
        $allowed = ['planned_start_at', 'planned_end_at', 'notes', 'vehicle_id', 'trailer_id', 'vehicle_cargo_id', 'trailer_cargo_id', 'driver_id', 'logistics_user_id', 'orders_truck', 'orders_trailer', 'orders_fulfill', 'stops'];
        abort_if(array_diff(array_keys($request->all()), $allowed), 422, 'Campo non previsto nel contratto viaggio.');
        $request->validate(['stops' => 'required|array|min:1', 'stops.*.sequence' => 'required|integer|distinct|min:1']);
        $input = $request->only($allowed);
        $selected = array_map('intval', array_merge($input['orders_truck'] ?? [], $input['orders_trailer'] ?? [], $input['orders_fulfill'] ?? []));
        $scheduled = array_map('intval', collect($input['stops'])->flatMap(fn ($s) => $s['orders'] ?? [])->all());
        sort($selected);
        sort($scheduled);
        abort_unless($selected === $scheduled, 422, 'Ogni ordine selezionato deve comparire in una sola tappa.');

        return $input;
    }
}
