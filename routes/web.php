<?php

use App\Models\User;
use Inertia\Inertia;
use GuzzleHttp\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IndexController;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\JourneyController;
use App\Http\Controllers\WorkerJourneyCargo;
use App\Http\Controllers\API_RecipeController;
use App\Http\Controllers\RecipeNodeController;
use App\Http\Controllers\CatalogItemController;
use App\Http\Controllers\DriverOrderController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserAccountController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\HolderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DriverJourneyController;
use App\Http\Controllers\TrailerController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\WithdrawController;
use App\Http\Controllers\NotificationSeenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CatalogItemRecipeController;
use App\Http\Controllers\OrderItemExplosionController;
use App\Http\Controllers\API_WarehouseOrdersController;
use App\Http\Controllers\JourneyCargoController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\API_DriverOrderUpdateController;
use App\Http\Controllers\WarehouseManagerOrderController;
use App\Http\Controllers\API_DriverJourneyUpdateController;
use App\Http\Controllers\API_WarehouseOrderItemsController;
use App\Http\Controllers\API_SiteTimetableController;
use App\Http\Controllers\WarehouseManagerOrderItemController;
use App\Http\Controllers\API_WarehouseJourneyCargosController;
use App\Http\Controllers\API_SiteBooleanUpdateController;
use App\Http\Controllers\API_UserResetAndResendController;
use App\Http\Controllers\API_DriverJourneyStopsController;
use App\Http\Controllers\API_OrderDocumentsController;
use App\Http\Controllers\WarehouseManagerOrderItemImageController;
use App\Http\Controllers\API_NlpLogisticsParseController;
use App\Http\Controllers\LogisticDispatchController;
use App\Http\Controllers\API_LogisticDispatchController;
use App\Http\Controllers\API_LogisticDispatchWorkspaceController;


//Route::get('/', [IndexController::class, 'login']);
//Route::get('/', [AuthController::class, 'create'])->name('login');
//Route::get('/hello', [IndexController::class, 'show'])->middleware('auth');

Route::get('/', fn() => to_route('login'));
//Route::get('/', [AuthController::class, 'create'])->name('login');
Route::get('login', [AuthController::class, 'create'])->name('login');
Route::post('login', [AuthController::class, 'store'])->name('login.store');
Route::delete('logout', [AuthController::class, 'destroy'])->name('logout');

// PASSWORD
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->middleware('guest')->name('password.request');

Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);
 
    $status = Password::sendResetLink(
        $request->only('email')
    );
 
    return $status === Password::RESET_LINK_SENT
                ? back()->with(['status' => __($status)])
                : back()->withErrors(['email' => __($status)]);
})->middleware('guest')->name('password.email');

Route::get('/reset-password/{token}', function (string $token) {
    //return view('auth.reset-password', ['token' => $token]);
    return Inertia::render('Auth/ResetPassword', ['token' => $token]);
})->middleware('guest')->name('password.reset');
 
Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);
 
    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->setRememberToken(Str::random(60));
 
            $user->save();
 
            event(new PasswordReset($user));
        }
    );
 
    return $status === Password::PASSWORD_RESET
                ? redirect()->route('login')->with('status', __($status))
                : back()->withErrors(['email' => [__($status)]]);
})->middleware('guest')->name('password.update');


// EMAIL VERIFICATION
Route::get('/email/verify', function() {
    return inertia('Auth/VerifyEmail');
})->middleware('auth')->name('verification.notice'); // name('verification.notice') is LARAVEL STANDARD

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
     //return redirect('/home');
     return redirect()->route('user-account.index')->with('success', 'Email correctly verified');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
 
    //return back()->with('message', 'Verification link sent!');
    return redirect()->back()->with('success', 'Link di verifica inviato alla casella email dell\'utente');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');  // throttle:6,1 means is rate limiting at no more than 6 time in 1 minute


Route::match(['put', 'post'], 'user-account/{user}', [UserAccountController::class, 'update'])
->middleware(['auth', 'verified'])
->name('user-account.update');

Route::resource('user-account', UserAccountController::class)
->middleware(['auth', 'verified'])
->only('create', 'store', 'index', 'edit');


// listings removed

Route::resource('notification', NotificationController::class)
->middleware('auth')
->only('index');

Route::put('notification/{notification}/seen', NotificationSeenController::class)
->middleware('auth')
->name('notification.seen');


// DASHBOARD PER RUOLO
// Entrata neutra
Route::get('/dashboard', fn() => null)
    ->middleware(['auth', 'verified', 'role.home.redirect'])
    ->name('dashboard');

// WAREHOUSE
Route::get('/dashboard-warehouse', fn() => Inertia::render('Dashboard/Warehouse'))
    ->middleware(['auth'])
    //->middleware(['auth', 'can:access-warehouse'])
    ->name('warehouse.home');

// LOGISTIC
Route::get('/dashboard-logistic', [DashboardController::class, 'logisticHome'])
    ->middleware(['auth', 'verified'])
    ->name('logistic.home');
Route::get('/dashboard-logistic/full', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.logistic.full');
Route::get('/dashboard-logistic/operations', [DashboardController::class, 'logisticOperations'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.logistic.operations');
Route::get('/logistic/dispatch', [LogisticDispatchController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('logistic-dispatch.index');
Route::get('/logistic/dispatch/{journey}', [LogisticDispatchController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('logistic-dispatch.show');


// DRIVER
Route::get('/dashboard-driver', fn() => Inertia::render('Dashboard/Driver'))
    ->middleware(['auth'])
    //->middleware(['auth', 'can:access-warehouse'])
    ->name('driver.home');

// CUSTOMER
Route::get('/dashboard-customer', fn() => Inertia::render('Dashboard/Customer'))
    ->middleware(['auth'])
    ->name('customer.home');

// MANAGER
Route::get('/dashboard-manager', fn() => Inertia::render('Dashboard/Manager'))
    ->middleware(['auth'])
    //->middleware(['auth', 'can:access-warehouse'])
    ->name('manager.home');

// DEVELOPER
Route::get('/dashboard-developer', fn() => Inertia::render('Dashboard/Developer'))
    ->middleware(['auth'])
    //->middleware(['auth', 'can:access-warehouse'])
    ->name('developer.home');
/*
// Fallback generica
Route::middleware('auth')->get('/home', fn() => Inertia::render('Generic/Home'))
    ->name('generic.home');
*/


Route::resource('journey', JourneyController::class)
->withTrashed();  

Route::name('journeyCargo.create')
->middleware(['auth', 'verified'])
->get(
    'journeyCargo/{journey}/create',
    [JourneyCargoController::class, 'create']
)->withTrashed();
Route::name('journeyCargo.edit')
->middleware(['auth', 'verified'])
->get(
    'journeyCargo/{journey}/edit',
    [JourneyCargoController::class, 'edit']
)->withTrashed();
Route::name('journeyCargo.manage')
->middleware(['auth', 'verified'])
->get(
    'journeyCargo/{journeyCargo}/manage',
    [JourneyCargoController::class, 'manage']
)->withTrashed();
Route::resource('journeyCargo', JourneyCargoController::class)
->middleware(['auth', 'verified'])
->only(['index', 'show', 'store', 'update', 'destroy'])
->withTrashed();

Route::resource('holder', HolderController::class)
->middleware(['auth', 'verified']);

// Backoffice resources (canonical, no relator prefix)
Route::middleware(['auth', 'verified'])->group(function() {
    // listings removed


    //CUSTOMER
    Route::name('customer.restore')
    ->put(
        'customer/{customer}/restore',
        [CustomerController::class, 'restore']
    )->withTrashed(); 
    Route::resource('customer', CustomerController::class)
    ->withTrashed();  

    // SITE
    Route::resource('site', SiteController::class)
    ->withTrashed();  

    // WITHDRAW
    Route::resource('withdraw', WithdrawController::class)
    ->only(['create', 'store', 'edit', 'update', 'destroy'])
    ->withTrashed();  

    Route::resource('order', OrderController::class)
    ->withTrashed();  

    Route::resource('vehicle', VehicleController::class)
    ->withTrashed();  

    Route::resource('trailer', TrailerController::class)
    ->withTrashed();  

    Route::resource('cargo', CargoController::class)
    ->withTrashed();  


    // USER
    Route::resource('user', UserController::class)
    ->withTrashed();
});


// DRIVER
Route::prefix('driver')
->name('driver.')
->middleware(['auth', 'verified'])
->group(function() {

    Route::resource('order', DriverOrderController::class)
    ->withTrashed();  

    Route::resource('journey', DriverJourneyController::class)
    ->withTrashed();  
});

// WORKER
Route::prefix('worker')
->name('worker.')
->middleware(['auth', 'verified'])
->group(function() {

    Route::resource('journeyCargo', WorkerJourneyCargo::class)
    ->withTrashed();  

});

// WAREHOUSE MANAGER
Route::prefix('warehouse-manager')
->name('warehouse-manager.')
//->middleware(['auth', 'role:warehouse_manager'])
->middleware(['auth'])
->group(function () {
    Route::get('order-items', [WarehouseManagerOrderItemController::class, 'index'])
    ->name('order-items.index');   
    Route::resource('orders', WarehouseManagerOrderController::class);
    //->only('create', 'store', 'destroy');
});
Route::prefix('warehouse-manager/orders')->group(function() {
    Route::get('{order}/items/create', [WarehouseManagerOrderItemController::class, 'create'])->name('warehouse.orders.items.create');
    Route::post('{order}/items', [WarehouseManagerOrderItemController::class, 'store'])->name('warehouse.orders.items.store');
});


Route::prefix('warehouse-manager/order-item')->group(function() {
    Route::get('{orderItem}/image', [WarehouseManagerOrderItemImageController::class, 'create'])->name('warehouse-manager.order-item.image.create');
    Route::post('{orderItem}/image/create', [WarehouseManagerOrderItemImageController::class, 'store'])->name('warehouse-manager.order-item.image.store');
    Route::delete('{orderItem}/image/{image}', [WarehouseManagerOrderItemImageController::class, 'destroy'])->name('warehouse-manager.order-item.image.destroy');
});

//// MARCON


Route::prefix('map')
->name('map.')
->middleware(['auth', 'verified'])
->group(function() {

    Route::resource('site', MapController::class)
    ->only(['index'])
    ->withTrashed();

});

/*
Route::get('/map', function() {
    return Inertia::render('Map/Index');
})->middleware('auth')->name('map.index'); 
*/



// routes/web.php
//Route::middleware(['auth', 'can:admin'])->group(function () {
Route::middleware(['auth'])->group(function () {
    Route::resource('catalog-items', CatalogItemController::class);
    Route::resource('recipes', RecipeController::class);

    // Recipe Nodes (AJAX)
    // usiamo RecipeNodeController per chiarezza, anche se non è una risorsa
    Route::get('/recipes/{recipe}/nodes',        [RecipeNodeController::class, 'index'])->name('recipes.nodes');
    Route::post('/recipes/{recipe}/nodes',       [RecipeNodeController::class, 'store'])->name('recipes.nodes.store');
    Route::put('/recipe-nodes/{node}',           [RecipeNodeController::class, 'update'])->name('recipes.nodes.update');
    Route::delete('/recipe-nodes/{node}',        [RecipeNodeController::class, 'destroy'])->name('recipes.nodes.destroy');
    Route::post('/recipe-nodes/{node}/replace-children', [RecipeNodeController::class, 'replaceChildren'])->name('recipes.nodes.replace-children');
    // opzionale: “default-tree” per importare la ricetta predefinita del componente
    Route::get('/recipes/default-tree', [API_RecipeController::class, 'defaultTree']);
    Route::put('/recipes/{recipe}/nodes/sync', [\App\Http\Controllers\RecipeNodeController::class, 'sync'])->name('recipes.nodes.sync');
 
    Route::get('/catalog-items/{item}/recipe', [CatalogItemRecipeController::class, 'editOrCreate'])->name('catalog-items.recipe.edit');

    Route::get('/order-items/{orderItem}/explosions', [OrderItemExplosionController::class, 'show'])->name('api.order-items.explosions.show');
    Route::put('/order-items/{orderItem}/explosions/sync', [OrderItemExplosionController::class, 'sync'])->name('api.order-items.explosions.sync');

});




// Define API routes separately, still using `web.php` for Inertia
Route::prefix('api')
    ->middleware(['auth'])
    ->group(function () {
        //Route::get('/timetable/{site}', [API_SiteTimetableController::class, 'show']);
        Route::post('/timetable/{site}', [API_SiteTimetableController::class, 'store']);
        Route::put('/site/updateBooleans/{site}', [API_SiteBooleanUpdateController ::class, 'update']);
        Route::post('/site/{site}/recalculate-risk', [API_SiteBooleanUpdateController::class, 'recalculateRisk']);
        Route::post('/customer/{customer}/recalculate-risk', [CustomerController::class, 'recalculateRisk']);
        Route::put('/journey/updateState/{journey}', [API_DriverJourneyUpdateController ::class, 'updateState']);
        Route::put('/order/updateState/{order}', [API_DriverOrderUpdateController ::class, 'updateState']);
        Route::post('/orders/{order}/generate-documents', [API_OrderDocumentsController::class, 'generate']);
        Route::get('/orders/{order}/document-status', [API_OrderDocumentsController::class, 'status']);
        Route::get('/orders/{order}/documents', [API_OrderDocumentsController::class, 'list']);
        Route::get('/orders/{order}/documents/{document}/download', [API_OrderDocumentsController::class, 'download'])
            ->where('document', '.*');
        Route::get('/journeys/{journey}/documents-status', [JourneyController::class, 'documentsStatus']);
        Route::post('/journeys/{journey}/generate-documents', [JourneyController::class, 'generateDocuments']);
        Route::post('/driver/journeys/{journey}/start', [API_DriverJourneyStopsController::class, 'startJourney']);
        Route::put('/driver/journeys/{journey}/stops/reorder', [API_DriverJourneyStopsController::class, 'reorder']);
        Route::put('/driver/journeys/{journey}/stops/{stop}/complete', [API_DriverJourneyStopsController::class, 'complete']);
        Route::put('/driver/journeys/{journey}/stops/{stop}/skip', [API_DriverJourneyStopsController::class, 'skip']);
        Route::post('/driver/journeys/{journey}/stops/technical', [API_DriverJourneyStopsController::class, 'createTechnical']);
        Route::put('/logistic/dispatch/{journey}/plan', [API_LogisticDispatchController::class, 'updatePlan'])->name('api.logistic-dispatch.update-plan');
        Route::post('/logistic/dispatch/{journey}/hold', [API_LogisticDispatchController::class, 'hold'])->name('api.logistic-dispatch.hold');
        Route::post('/logistic/dispatch/{journey}/resume', [API_LogisticDispatchController::class, 'resume'])->name('api.logistic-dispatch.resume');
        Route::get('/logistic/dispatch/{journey}/workspace', [API_LogisticDispatchWorkspaceController::class, 'workspace'])->name('api.logistic-dispatch.workspace');
        Route::put('/logistic/dispatch/{journey}/cargos', [API_LogisticDispatchWorkspaceController::class, 'upsertCargos'])->name('api.logistic-dispatch.cargos.save');
        Route::put('/logistic/dispatch/{journey}/workspace', [API_LogisticDispatchWorkspaceController::class, 'saveWorkspace'])->name('api.logistic-dispatch.workspace.save');
        Route::post('/logistic/dispatch/{journey}/confirm', [API_LogisticDispatchWorkspaceController::class, 'confirm'])->name('api.logistic-dispatch.confirm');
        Route::post('/logistic/dispatch/{journey}/events', [API_LogisticDispatchWorkspaceController::class, 'appendEvent'])->name('api.logistic-dispatch.events');
        Route::post('/logistic/transshipments/{transshipment}/approve', [API_LogisticDispatchWorkspaceController::class, 'approveTransshipment'])->name('api.logistic-transshipments.approve');
        Route::post('/logistic/transshipments/{transshipment}/cancel', [API_LogisticDispatchWorkspaceController::class, 'cancelTransshipment'])->name('api.logistic-transshipments.cancel');
        Route::post('/logistic/dispatch/{journey}/close', [API_LogisticDispatchWorkspaceController::class, 'close'])->name('api.logistic-dispatch.close');
        Route::post('/nlp/logistics/parse', [API_NlpLogisticsParseController::class, 'parse']);
        Route::put('/warehouse-orders/{order}', [API_WarehouseOrdersController::class, 'update'])->name('update');
        Route::post('/warehouse-order-items/move-journey-cargo/{orderItem}', [API_WarehouseOrderItemsController::class, 'moveJourneyCargo'])->name('warehouse-order-items.move-journey-cargo');
        Route::post('/warehouse-order-items/save-items', [API_WarehouseOrderItemsController::class, 'saveItems'])->name('warehouse-order-items.save-items-bulk');
        /* Forzo temporaneamente il api. per un problema axios che al momento non riesco a debuggare */
        Route::patch('/warehouse-order-items/not-found/{orderItem}', [API_WarehouseOrderItemsController::class, 'flagNotFound'])->name('api.warehouse-order-items.flag-not-found');
        Route::put('/warehouse-order-items/{orderItem}', [API_WarehouseOrderItemsController::class, 'update'])->name('warehouse-order-items.update');
        Route::put('warehouse-journey-cargos/{journeyCargo}', [API_WarehouseJourneyCargosController::class, 'update'])->name('warehouse-journey-cargos.update');
        Route::post('/user/resend-verification/{user}', [API_UserResetAndResendController::class, 'resendVerification'])->name('user.resend.verification');
        Route::post('/user/send-password-reset/{user}', [API_UserResetAndResendController::class, 'sendPasswordResetEmail'])->name('user.send.password.reset');

        /*
         * ORDER ITEM EXPLOSION (ESPLOSIONE COLLO)
         */
        Route::prefix('order-items/{orderItem}')->group(function () {
        Route::post('explosions', [OrderItemExplosionController::class, 'store']);            // ad-hoc
        Route::post('explode/recipe', [OrderItemExplosionController::class, 'applyRecipe']);  // da ricetta
        });
        Route::prefix('order-item-explosions')->group(function () {
        Route::put('{id}',    [OrderItemExplosionController::class, 'update']);
        Route::delete('{id}', [OrderItemExplosionController::class, 'destroy']);
        });

        Route::get('/catalog-items', [CatalogItemController::class, 'search']);
        Route::post('/catalog-items', [CatalogItemController::class, 'store']); // inline creation
        
        Route::get('/recipes/default-tree', [API_RecipeController::class, 'defaultTree']); // ?catalog_item_id=123
        Route::get('/recipes/{recipe}/tree', [API_RecipeController::class, 'recipeTree'])->name('api.recipes.tree');

    });


Route::prefix('withdraws/{withdraw}')->group(function () {
    Route::put('/update-state', [WithdrawController::class, 'updateState'])->name('withdraws.update-state');
    Route::post('/attach-file', [WithdrawController::class, 'attachFile'])->name('withdraws.attach-file'); // pronta per quando la faremo
    // Other state-related actions
});
