<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\ReportingRelationshipController;
use App\Http\Controllers\Api\ScenarioController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

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

        // Set scenario as current
        Route::put('scenarios/{scenario}/set-current', [ScenarioController::class, 'setCurrent']);

        // Compare scenarios
        Route::get('compare-scenarios', [ScenarioController::class, 'compareScenarios']);

        // Organization metrics
        Route::get('metrics', [OrganizationController::class, 'metrics']);
    });
});