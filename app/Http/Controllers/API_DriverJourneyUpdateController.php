<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Enums\JourneysState;
use Illuminate\Http\Request;

class API_DriverJourneyUpdateController extends Controller
{
    
    /*
        CUSTOM actions for Journey life cycle 
    */
    public function updateState(Journey $journey, Request $request)
    {
        $newState = JourneysState::from($request->new_state);

        if (!JourneysState::from($journey->state->value)->canTransitionTo($newState)) {
            abort(403, 'Invalid state transition.');
        }

        /*
        case STATE_CREATED = 'creato';
        case STATE_ACTIVE = 'attivo';
        case STATE_EXECUTED = 'eseguito';
        case STATE_CLOSED = 'chiuso';
        */

        // Add lifecycle-specific logic
        switch ($newState) {
            case JourneysState::STATE_CREATED:
                $journey->created_at = $request->created_at;
                break;

            case JourneysState::STATE_ACTIVE:
                $journey->real_dt_start = $request->real_dt_start;
                break;

            case JourneysState::STATE_EXECUTED:
                $journey->real_dt_end = $request->real_dt_end;
                break;

            case JourneysState::STATE_CLOSED:
                // Attachments or warehouse updates
                $journey->downloaded_files = $request->file('attachments')->store('journeys');
                break;
        }

        $journey->state = $newState->value;
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
            $journey->load(['owner', 'journeys', 'timetable', 'internal_contacts']);
        }
    
        return response()->json(['message' => 'Journey saved successfully.', 'journey' => $journey], 200);
    }
}
