<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;

class API_DriverOrderUpdateController extends Controller
{
    
    /*
        CUSTOM actions for Order life cycle 
    */
    public function updateState(Order $order, Request $request)
    {
        $newState = OrderStatus::from($request->new_state);

        if (!OrderStatus::from($order->status->value)->canTransitionTo($newState)) {
            abort(403, 'Invalid state transition.');
        }

        /*
        case STATUS_CREATED = 'creato';
        case STATUS_PLANNED = 'pianificato';
        case STATUS_EXECUTED = 'eseguito';
        case STATUS_DOWNLOADED = 'scaricato';
        case STATUS_CLOSED = 'chiuso';
        */

        // Add lifecycle-specific logic
        switch ($newState) {
            case OrderStatus::STATUS_CREATED:
                $order->created_at = $request->created_at;
                break;

            case OrderStatus::STATUS_EXECUTED:
                $order->actual_withdraw_at = $request->actual_withdraw_at;
                break;

            case OrderStatus::STATUS_CLOSED:
                // Attachments or warehouse updates
                $order->downloaded_files = $request->file('attachments')->store('orders');
                break;
        }

        $order->status = $newState->value;
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
            $order->load(['customer', 'orders', 'timetable', 'internal_contacts']);
        }
    
        return response()->json(['message' => 'Order saved successfully.', 'order' => $order], 200);
    }
}





