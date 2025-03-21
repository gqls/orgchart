<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ReportingRelationshipController;
use App\Http\Controllers\Api\ScenarioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserSettingsController;
use App\Http\Controllers\Api\OrganizationUserController;
use App\Models\Organization;
use App\Http\Controllers\Api\ActivityLogController;


Route::get('/debug', function() {

})->middleware('debugbar');
// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/roles', [RoleController::class, 'index']);

Route::get('/test-binding/{organization}', function (Organization $organization) {
    return response()->json($organization);
});

Route::get('/test-login', function () {
    return view('test-login');
});

// In routes/api.php
Route::get('/auth-debug', function (Request $request) {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->user(),
        'token_present' => $request->bearerToken() ? true : false,
        'token_value' => $request->bearerToken() ? substr($request->bearerToken(), 0, 10).'...' : null,
    ]);
});

Route::get('/auth-test', function () {
    if (auth()->check()) {
        return response()->json(['authenticated' => true, 'user' => auth()->user()]);
    }
    return response()->json(['authenticated' => false], 401);
});//->middleware('auth:sanctum');

Route::get('/test-controller', [App\Http\Controllers\Api\TestController::class, 'index']);

Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

// routes/api.php
Route::get('/test-auth', function (Request $request) {
    return response()->json([
        'headers' => $request->headers->all(),
        'user' => auth()->user(),
        'session' => session()->all(),
        'cookies' => $request->cookies->all()
    ]);
});//->middleware('auth:sanctum');

// Protected routes
//Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Health check route
    Route::get('/health', function () {
        return response()->json(['status' => 'ok']);
    });

    // Organizations
    Route::apiResource('organizations', OrganizationController::class);

    // Nested resources
    Route::prefix('organizations/{organization}')->group(function () {
        // Departments
        Route::apiResource('departments', DepartmentController::class);

        // Positions
        Route::apiResource('positions', PositionController::class);

        // Reporting relationships
        Route::apiResource('relationships', ReportingRelationshipController::class);

        // Scenarios
        Route::apiResource('scenarios', ScenarioController::class);
        Route::put('scenarios/{scenario}/set-current', [ScenarioController::class, 'setCurrent']);

        // Scenario positions
        Route::get('scenarios/{scenario}/positions', [ScenarioController::class, 'positions']);
        Route::post('scenarios/{scenario}/positions', [ScenarioController::class, 'addPosition']);
        Route::put('scenarios/{scenario}/positions/{position}', [ScenarioController::class, 'updatePosition']);
        Route::delete('scenarios/{scenario}/positions/{position}', [ScenarioController::class, 'removePosition']);

        // Scenario detail
        Route::get('scenarios/{scenario}/detail', [ScenarioController::class, 'detail']);

        // Scenario relationships
        Route::get('scenarios/{scenario}/relationships', [ScenarioController::class, 'relationships']);
        Route::post('scenarios/{scenario}/relationships', [ScenarioController::class, 'addRelationship']);
        Route::put('scenarios/{scenario}/relationships/{relationship}', [ScenarioController::class, 'updateRelationship']);
        Route::delete('scenarios/{scenario}/relationships/{relationship}', [ScenarioController::class, 'removeRelationship']);

        // Scenario metrics
        Route::get('scenarios/{scenario}/metrics', [ScenarioController::class, 'metrics']);
        Route::post('scenarios/{scenario}/calculate-metrics', [ScenarioController::class, 'calculateMetrics']);

        // Compare scenarios
        Route::get('compare-scenarios', [ScenarioController::class, 'compareScenarios']);

        // Organization metrics
        Route::get('metrics', [OrganizationController::class, 'metrics']);

    });

    // User Settings
    Route::post('user/two-factor-authentication', [UserSettingsController::class, 'updateTwoFactorAuth']);
    Route::get('user/tokens', [UserSettingsController::class, 'listTokens']);
    Route::post('user/tokens', [UserSettingsController::class, 'createToken']);
    Route::delete('user/tokens/{token}', [UserSettingsController::class, 'revokeToken']);
    Route::post('user/notification-preferences', [UserSettingsController::class, 'updateNotificationPreferences']);

    // Organization Users
    Route::get('organizations/{organization}/users', [OrganizationUserController::class, 'index']);
    Route::post('organizations/{organization}/users/invite', [OrganizationUserController::class, 'invite']);
    Route::put('organizations/{organization}/users/{user}', [OrganizationUserController::class, 'update']);
    Route::delete('organizations/{organization}/users/{user}', [OrganizationUserController::class, 'remove']);

    // Activity Logs
    Route::get('organizations/{organization}/activity-logs', [ActivityLogController::class, 'index']);
    Route::get('organizations/{organization}/activity-logs/{activityLog}', [ActivityLogController::class, 'show']);

//});
