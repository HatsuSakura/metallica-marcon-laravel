<?php
namespace App\Http\Controllers;
use App\Models\Journey;
use Illuminate\Http\Request;
use App\Services\JourneyExecution\JourneyExecutionService;

class API_DriverJourneyUpdateController extends Controller
{
    public function updateState(Journey $journey, Request $request)
    {
        $controller = app(API_JourneyExecutionController::class);
        if ($request->input('new_state') === 'attivo') return $controller->start($request, $journey, app(JourneyExecutionService::class));
        abort_unless($request->input('new_state') === 'eseguito', 422, 'Usa i comandi del contesto esecutivo.');
        $request->merge(['operation' => 'finish', 'input' => []]);
        return $controller->command($request, $journey, app(JourneyExecutionService::class));
    }
}
