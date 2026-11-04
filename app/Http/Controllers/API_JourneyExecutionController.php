<?php

namespace App\Http\Controllers;

use App\Models\Journey;
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
        $events = $journey->events()->orderByDesc('id')->get()->filter(fn ($e) => in_array($e->payload['event'] ?? '', ['execution_started', 'execution_changed']))->values();
        $actors = \App\Models\User::without('warehouses')->whereIn('id', $events->pluck('created_by_user_id'))->get()->keyBy('id');

        return response()->json(['baseline' => $baseline, 'events' => $events->map(fn ($e) => [
            'id' => $e->id, 'created_at' => $e->created_at, 'payload' => $e->payload,
            'actor' => trim(($actors[$e->created_by_user_id]->name ?? '').' '.($actors[$e->created_by_user_id]->surname ?? '')) ?: 'Sistema',
        ])]);
    }

    public function start(Request $request, Journey $journey, JourneyExecutionService $service)
    {
        Gate::authorize('update', $journey);
        $j = $service->start($journey, $request->user()->id);

        return response()->json(['journey' => app(DriverJourneyReadModel::class)->load($j)]);
    }

    public function command(Request $request, Journey $journey, JourneyExecutionService $service)
    {
        Gate::authorize(in_array($request->input('operation'), ['warehouse_item', 'close'], true) ? 'warehouseManage' : 'update', $journey);
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:1'],
            'operation' => ['required', Rule::in(['set_item_allocations', 'set_item_loaded', 'reorder', 'complete', 'skip', 'reopen', 'add_stop', 'update_journey', 'update_order', 'add_item', 'update_item', 'remove_item', 'add_holder', 'update_holder', 'restore_original', 'restore_previous', 'undo_restore', 'finish', 'warehouse_item', 'close'])],
            'input' => ['present', 'array'],
        ]);
        $operation = $data['operation'];
        $rules = match ($operation) {
            'set_item_allocations' => ['item_id' => 'required|integer', 'vehicle_containers' => 'required|integer|min:0', 'trailer_containers' => 'required|integer|min:0'],
            'set_item_loaded' => ['item_id' => 'required|integer', 'is_loaded' => 'required|boolean'],
            'reorder' => ['stop_ids' => 'required|array|min:1', 'stop_ids.*' => 'required|integer|distinct'],
            'complete' => ['stop_id' => 'required|integer', 'confirm_current_quantities' => 'boolean'],
            'skip' => ['stop_id' => 'required|integer', 'reason_code' => ['required', Rule::in(['traffic', 'over_capacity', 'customer_closed', 'customer_refused', 'vehicle_issue', 'other'])], 'driver_notes' => 'nullable|string|max:2000|required_if:reason_code,other'],
            'reopen' => ['stop_id' => 'required|integer'],
            'add_stop' => ['kind' => ['required', Rule::in(['customer', 'technical'])], 'source_order_id' => 'nullable|integer|exists:orders,id',
                'customer_id' => 'nullable|integer|exists:customers,id', 'site_id' => 'nullable|integer|exists:sites,id',
                'technical_action_id' => 'required_if:kind,technical|nullable|integer|exists:journey_stop_actions,id', 'description' => 'nullable|string|max:255', 'address_text' => 'nullable|string|max:255'],
            'update_journey' => ['notes' => 'nullable|string|max:5000', 'vehicle_id' => 'missing', 'trailer_id' => 'missing', 'vehicle' => 'missing', 'trailer' => 'missing', 'vehicle_cargo_id' => 'missing', 'trailer_cargo_id' => 'missing'],
            'update_order' => ['execution_order_id' => 'required|integer', 'notes' => 'nullable|string|max:5000', 'cargo_location' => ['sometimes', Rule::in(['vehicle', 'trailer', 'fulfill'])]],
            'add_item', 'update_item' => ['execution_order_id' => ($operation === 'add_item' ? 'required' : 'nullable').'|integer', 'item_id' => ($operation === 'update_item' ? 'required' : 'nullable').'|integer',
                'cer_code_id' => ($operation === 'add_item' ? 'required' : 'sometimes').'|integer|exists:cer_codes,id', 'holder_id' => 'nullable|integer|exists:holders,id',
                'holder_quantity' => 'nullable|integer|min:0', 'weight_declared' => 'nullable|numeric|min:0', 'description' => 'nullable|string|max:2000', 'is_bulk' => 'boolean',
                'actual_containers' => 'nullable|integer|min:0', 'actual_weight_kg' => 'nullable|numeric|min:0', 'cargo_location' => ['sometimes', 'nullable', Rule::in(['vehicle', 'trailer'])],
                'order_item_group_label' => 'nullable|string|max:120', 'warehouse_id' => 'nullable|integer|exists:warehouses,id'],
            'remove_item' => ['item_id' => 'required|integer'],
            'add_holder' => ['execution_order_id' => 'required|integer', 'holder_id' => 'required|integer|exists:holders,id', 'total_holders_count' => 'required|integer|min:0'],
            'update_holder' => ['holder_id' => 'required|integer', 'total_holders_count' => 'sometimes|required|integer|min:0', 'actual_delivered' => 'nullable|integer|min:0'],
            'restore_original', 'restore_previous' => ['item_id' => 'required|integer', 'field' => 'required|string'],
            'warehouse_item' => ['item_id' => 'required|integer', 'warehouse_id' => 'required|integer|exists:warehouses,id',
                'weight_gross' => 'nullable|numeric|min:0', 'weight_tare' => 'nullable|numeric|min:0',
                'warehouse_notes' => 'nullable|string|max:5000', 'warehouse_status' => ['required', Rule::in(['received', 'weighed', 'classified'])]],
            'undo_restore' => ['event_id' => 'required|integer'],
            'finish', 'close' => [],
        };
        $messages = [];
        foreach (['vehicle_id', 'trailer_id', 'vehicle', 'trailer', 'vehicle_cargo_id', 'trailer_cargo_id'] as $field) {
            $messages[$field.'.missing'] = 'Il convoglio è fissato alla partenza: motrice e rimorchio non possono essere modificati durante il viaggio.';
        }
        $input = validator($data['input'], $rules, $messages)->validate();
        if ($operation === 'add_stop' && $input['kind'] === 'customer' && empty($input['source_order_id'])) {
            validator($input, ['customer_id' => 'required', 'site_id' => 'required'])->validate();
        }
        if ($operation === 'set_item_allocations') {
            $input['vehicle_containers'] = (int) $input['vehicle_containers'];
            $input['trailer_containers'] = (int) $input['trailer_containers'];
        }
        $j = $service->command($journey, $request->user()->id, $data['revision'], $operation, $input);

        $undo = null;
        if (in_array($operation, ['restore_original', 'restore_previous'], true) && $j->execution_revision > $data['revision']) {
            $event = $j->events()->where('payload->revision', $j->execution_revision)->where('payload->operation', $operation)->firstOrFail();
            $undo = ['event_id' => $event->id, 'revision' => $j->execution_revision];
        }

        return response()->json(['journey' => app(DriverJourneyReadModel::class)->load($j), 'undo' => $undo]);
    }
}
