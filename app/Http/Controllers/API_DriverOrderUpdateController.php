<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Enums\OrdersState;
use Illuminate\Http\Request;

class API_DriverOrderUpdateController extends Controller
{
    
    /*
        CUSTOM actions for Order life cycle 
    */
    public function updateState(Order $order, Request $request)
    {
        $newState = OrdersState::from($request->new_state);

        if (!OrdersState::from($order->state->value)->canTransitionTo($newState)) {
            abort(403, 'Invalid state transition.');
        }

        /*
        case STATE_CREATED = 'creato';
        case STATE_PLANNED = 'pianificato';
        case STATE_EXECUTED = 'eseguito';
        case STATE_DOWNLOADED = 'scaricato';
        case STATE_CLOSED = 'chiuso';
        */

        // Add lifecycle-specific logic
        switch ($newState) {
            case OrdersState::STATE_CREATED:
                $order->created_at = $request->created_at;
                break;

            case OrdersState::STATE_EXECUTED:
                $order->real_withdraw_dt = $request->real_withdraw_dt;
                break;

            case OrdersState::STATE_CLOSED:
                // Attachments or warehouse updates
                $order->downloaded_files = $request->file('attachments')->store('orders');
                break;
        }

        $order->state = $newState->value;
        $order->save();

        return response()->json(['type' => 'success','message' => "Order state updated to {$newState->value}."], 200);
    }




    public function update(Request $request, Order $order) {
        $validated = $request->validate([
        ]);
    
        $order->update(
            $validated
        );

        // Check if full data is requested
        if ($request->query('full', false)) {
            $order->load(['owner', 'orders', 'timetable', 'internal_contacts']);
        }
    
        return response()->json(['message' => 'Order saved successfully.', 'order' => $order], 200);
    }
}
