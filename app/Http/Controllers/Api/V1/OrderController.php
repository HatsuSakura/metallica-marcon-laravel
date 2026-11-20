<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Planning\OrderWriter;
use App\Services\Planning\PlanningGuard;
use App\Support\AgentApi\Representation;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private Representation $view, private OrderWriter $writer) {}

    public function index(Request $request)
    {
        $f = $request->validate(['customer_id' => 'nullable|integer', 'status' => 'nullable|in:creato,pronto,pianificato,eseguito,scaricato,chiuso', 'per_page' => 'nullable|integer|min:1|max:100']);

        return Order::query()->when(isset($f['customer_id']), fn ($q) => $q->where('customer_id', $f['customer_id']))
            ->when(isset($f['status']), fn ($q) => $q->where('status', $f['status']))->orderByDesc('id')
            ->paginate($f['per_page'] ?? 25)->through(fn ($order) => $this->view->order($order));
    }

    public function show(Order $order)
    {
        $data = $this->view->order($order);

        return response()->json(['data' => $data])->header('ETag', '"'.$data['version'].'"');
    }

    public function store(Request $request)
    {
        $data = $this->view->order($this->writer->create($this->input($request)));

        return response()->json(['data' => $data], 201)->header('ETag', '"'.$data['version'].'"');
    }

    public function update(Request $request, Order $order)
    {
        app(PlanningGuard::class)->order($order);
        $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
        $current = $this->view->order($order);
        $this->view->checkVersion($request, $current);
        $input = array_replace($current, $this->input($request));
        $data = $this->view->order($this->writer->update($order, $input)->refresh());

        return response()->json(['data' => $data])->header('ETag', '"'.$data['version'].'"');
    }

    private function input(Request $request): array
    {
        $allowed = ['customer_id', 'site_id', 'logistics_user_id', 'requested_at', 'expected_withdraw_at', 'fixed_withdraw_at', 'is_urgent', 'notes', 'items', 'holders'];
        abort_if(array_diff(array_keys($request->all()), $allowed), 422, 'Campo non previsto nel contratto ordine.');

        return $request->only($allowed);
    }

    public function items(Order $order)
    {
        $data = $this->view->order($order);

        return response()->json(['data' => $data['items'], 'order_version' => $data['version']])->header('ETag', '"'.$data['version'].'"');
    }

    public function showItem(Order $order, int $item)
    {
        $data = $this->view->order($order);
        $found = collect($data['items'])->firstWhere('id', $item);
        abort_unless($found, 404);

        return response()->json(['data' => $found, 'order_version' => $data['version']])->header('ETag', '"'.$data['version'].'"');
    }

    public function storeItem(Request $request, Order $order)
    {
        return $this->mutateItem($request, $order, null, 'create');
    }

    public function updateItem(Request $request, Order $order, int $item)
    {
        return $this->mutateItem($request, $order, $item, 'update');
    }

    public function destroyItem(Request $request, Order $order, int $item)
    {
        return $this->mutateItem($request, $order, $item, 'delete');
    }

    private function mutateItem(Request $request, Order $order, ?int $id, string $operation)
    {
        app(PlanningGuard::class)->order($order);
        $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
        $current = $this->view->order($order);
        $this->view->checkVersion($request, $current);
        abort_if(array_diff(array_keys($request->all()), array_diff(Representation::ITEM_FIELDS, ['id'])), 422, 'Campo materiale non previsto.');
        $items = collect($current['items']);
        if ($id) {
            abort_unless($items->contains('id', $id), 404);
        }
        if ($operation === 'create') {
            $items->push($request->all());
        } elseif ($operation === 'delete') {
            $items = $items->reject(fn ($i) => (int) $i['id'] === $id);
        } else {
            $items = $items->map(fn ($i) => (int) $i['id'] === $id ? array_replace($i, $request->all()) : $i);
        }
        $data = $this->view->order($this->writer->update($order, array_replace($current, ['items' => $items->values()->all()]))->refresh());

        return response()->json(['data' => $data], $operation === 'create' ? 201 : 200)->header('ETag', '"'.$data['version'].'"');
    }
}
