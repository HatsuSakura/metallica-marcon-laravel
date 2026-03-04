<?php

namespace App\Http\Controllers;

use App\Models\JourneyCargo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;


class API_WarehouseJourneyCargosController extends Controller{

    public function update(Request $request, JourneyCargo $journeyCargo) {
        $validated = $request->validate([
            'has_crane' => 'required|boolean',
            'crane_operator_user_id' => 'required|integer',
            'machinery_time_minutes' => 'nullable|integer',
        ]);
    
        $journeyCargo->update(
            $validated
        );
    
        return response()->json(['message' => 'JourneyCargo saved successfully.', 'journeyCargo' => $journeyCargo], 200);
    }

}




