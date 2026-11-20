<?php

use App\Http\Controllers\Api\V1\JourneyController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ReferenceController;
use App\Http\Middleware\AgentWriteRequest;
use App\Http\Middleware\AuthenticateAgent;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('agent-api.')->middleware(['auth:sanctum', 'throttle:agent-api'])->group(function () {
    Route::get('cer-codes', [\App\Http\Controllers\Api\V1\CerCodeController::class, 'index'])->middleware(AuthenticateAgent::class.':cer-codes:read')->name('cer-codes.index');
    Route::get('cer-codes/{cerCode}', [\App\Http\Controllers\Api\V1\CerCodeController::class, 'show'])->whereNumber('cerCode')->middleware(AuthenticateAgent::class.':cer-codes:read')->name('cer-codes.show');
    Route::get('references/{resource}', [ReferenceController::class, 'index'])->middleware(AuthenticateAgent::class.':reference:read')->name('references.index');
    foreach (['orders' => OrderController::class, 'journeys' => JourneyController::class] as $resource => $controller) {
        $model = $resource === 'orders' ? 'order' : 'journey';
        Route::get($resource, [$controller, 'index'])->middleware(AuthenticateAgent::class.":{$resource}:read")->name($resource.'.index');
        Route::get($resource.'/{'.$model.'}', [$controller, 'show'])->middleware(AuthenticateAgent::class.":{$resource}:read")->name($resource.'.show');
        Route::post($resource, [$controller, 'store'])->middleware([AuthenticateAgent::class.":{$resource}:create", AgentWriteRequest::class])->name($resource.'.store');
        Route::patch($resource.'/{'.$model.'}', [$controller, 'update'])->middleware([AuthenticateAgent::class.":{$resource}:update", AgentWriteRequest::class])->name($resource.'.update');
    }
    Route::get('orders/{order}/items', [OrderController::class, 'items'])->middleware(AuthenticateAgent::class.':orders:read')->name('items.index');
    Route::get('orders/{order}/items/{item}', [OrderController::class, 'showItem'])->whereNumber('item')->middleware(AuthenticateAgent::class.':orders:read')->name('items.show');
    Route::post('orders/{order}/items', [OrderController::class, 'storeItem'])->middleware([AuthenticateAgent::class.':orders:update', AgentWriteRequest::class])->name('items.store');
    Route::patch('orders/{order}/items/{item}', [OrderController::class, 'updateItem'])->whereNumber('item')->middleware([AuthenticateAgent::class.':orders:update', AgentWriteRequest::class])->name('items.update');
    Route::delete('orders/{order}/items/{item}', [OrderController::class, 'destroyItem'])->whereNumber('item')->middleware([AuthenticateAgent::class.':orders:update', AgentWriteRequest::class])->name('items.destroy');
});
