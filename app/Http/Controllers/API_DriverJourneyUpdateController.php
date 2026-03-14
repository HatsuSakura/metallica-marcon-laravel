<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Enums\JourneyStatus;
use Illuminate\Http\Request;

class API_DriverJourneyUpdateController extends Controller
{
    
    /*
        CUSTOM actions for Journey life cycle 
    */
    public function updateState(Journey $journey, Request $request)
    {
        $newState = JourneyStatus::from($request->new_state);

        if (!JourneyStatus::from($journey->status->value)->canTransitionTo($newState)) {
            abort(403, 'Invalid state transition.');
        }

        /*
        case STATUS_CREATED = 'creato';
        case STATUS_ACTIVE = 'attivo';
        case STATUS_EXECUTED = 'eseguito';
        case STATUS_CLOSED = 'chiuso';
        */

        // Add lifecycle-specific logic
        switch ($newState) {
            case JourneyStatus::STATUS_CREATED:
                $journey->created_at = $request->created_at;
                break;

            case JourneyStatus::STATUS_ACTIVE:
                $journey->actual_start_at = $request->actual_start_at;
                break;

            case JourneyStatus::STATUS_EXECUTED:
                $journey->actual_end_at = $request->actual_end_at;
                break;

            case JourneyStatus::STATUS_CLOSED:
                // Attachments or warehouse updates
                $journey->downloaded_files = $request->file('attachments')->store('journeys');
                break;
        }

        $journey->status = $newState->value;
        $journey->save();

        return response()->json(['type' => 'success','message' => "Journey state updated to {$newState->value}."], 200);
    }




    public function update(Request $request, Journey $journey) {
        $validated = $request->validate([
        ]);
    
        $journey->update(
            $validated
        );

        // Check if full data is requested
        if ($request->query('full', false)) {
            $journey->load(['customer', 'journeys', 'timetable', 'internal_contacts']);
        }
    
        return response()->json(['message' => 'Journey saved successfully.', 'journey' => $journey], 200);
    }
}





