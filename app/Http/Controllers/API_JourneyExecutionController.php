<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use App\Models\Journey;
use App\Models\User;
use App\Services\Driver\DriverExecutionLookups;
use App\Services\Driver\DriverJourneyReadModel;
use App\Services\JourneyExecution\JourneyExecutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class API_JourneyExecutionController extends Controller
{
    public function read(Request $request, Journey $journey)
    {
        Gate::authorize('view', $journey);

        return response()->json(['journey' => app(DriverJourneyReadModel::class)->load($journey)]);
    }

    public function history(Request $request, Journey $journey)
    {
        Gate::authorize('viewExecutionHistory', $journey);
        $baseline = $journey->baseline()->firstOrFail();
        $events = $journey->events()->orderByDesc('id')->get()->filter(fn ($e) => in_array($e->payload['event'] ?? '', ['execution_started', 'execution_changed', 'site_notes_updated']))->values();
        $actors = User::without('warehouses')->whereIn('id', $events->pluck('created_by_user_id'))->get()->keyBy('id');

        return response()->json(['baseline' => $baseline, 'events' => $events->map(fn ($e) => [
            'id' => $e->id, 'created_at' => $e->created_at, 'payload' => $e->payload,
            'actor' => trim(($actors[$e->created_by_user_id]->name ?? '').' '.($actors[$e->created_by_user_id]->surname ?? '')) ?: 'Sistema',
        ])]);
    }

    public function start(Request $request, Journey $journey, JourneyExecutionService $service)
    {
        Gate::authorize('update', $journey);
        $j = $service->start($journey, $request->user()->id);

        return response()->json(['journey' => app(DriverJourneyReadModel::class)->load($j), 'executionLookups' => app(DriverExecutionLookups::class)->load($j)]);
    }

    public function preferences(Request $request, Journey $journey)
    {
        Gate::authorize('update', $journey);
        $data = $request->validate(['header_open_on_entry' => 'required|boolean']);
        // Presentation preference: never mutate the planning or execution revisions.
        DB::table('journeys')->where('id', $journey->id)->update($data);
        return response()->json(['preferences' => ['header_open_on_entry' => (bool) $data['header_open_on_entry']]]);
    }

    public function command(Request $request, Journey $journey, JourneyExecutionService $service)
    {
        Gate::authorize($request->input('operation') === 'cancel_stop' ? 'viewExecutionHistory' : (in_array($request->input('operation'), ['warehouse_item', 'close'], true) ? 'warehouseManage' : 'update'), $journey);
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:1'],
            'intervention_id' => ['nullable','uuid'],
            'operation' => ['required', Rule::in(\App\Services\JourneyExecution\ExecutionCommandValidator::OPERATIONS)],
            'input' => ['present', 'array'],
        ]);
        $operation = $data['operation'];
        $input = app(\App\Services\JourneyExecution\ExecutionCommandValidator::class)->validate($request->user(), $operation, $data['input']);
        $id = $data['intervention_id'] ?? (string) \Illuminate\Support\Str::uuid();
        $result = app(\App\Services\JourneyExecution\ExecutionInterventions::class)->run($journey,$request->user(),$data['revision'],$id,
            [['id'=>$id,'operation'=>$operation,'input'=>$input]]);
        $j = $result['journey'];

        $undo = null;
        if (!$result['replayed'] && in_array($operation, ['restore_original', 'restore_previous'], true) && $j->execution_revision > $data['revision']) {
            $event = $j->events()->where('payload->revision', $j->execution_revision)->where('payload->operation', $operation)->firstOrFail();
            $undo = ['event_id' => $event->id, 'revision' => $j->execution_revision];
        }

        return response()->json(['journey' => app(DriverJourneyReadModel::class)->load($j), 'undo' => $undo]);
    }
    public function intervention(Request $request, Journey $journey)
    {
        Gate::authorize('viewExecutionHistory',$journey);
        Gate::authorize('update',$journey);
        $data = $request->validate([
            'intervention_id'=>['required','uuid'],'revision'=>['required','integer','min:1'],
            'preview'=>['sometimes','boolean'],'commands'=>['required','array','min:1','max:50'],
            'commands.*.id'=>['required','uuid','distinct'],
            'commands.*.operation'=>['required',Rule::in(\App\Services\JourneyExecution\ExecutionInterventions::BATCH_OPERATIONS)],
            'commands.*.input'=>['present','array'],
        ]);
        $preview = (bool)($data['preview'] ?? false);
        $result = app(\App\Services\JourneyExecution\ExecutionInterventions::class)->run($journey,$request->user(),$data['revision'],$data['intervention_id'],$data['commands'],$preview,true);
        if (!$preview) $result['journey'] = app(DriverJourneyReadModel::class)->load($result['journey']);
        return response()->json($result);
    }

}
