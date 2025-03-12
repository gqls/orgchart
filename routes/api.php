<?php

use App\app\app\Http\Controllers\Api\AuthController;
use App\app\app\Http\Controllers\Api\DepartmentController;
use App\app\app\Http\Controllers\Api\OrganizationController;
use App\app\app\Http\Controllers\Api\PositionController;
use App\app\app\Http\Controllers\Api\ReportingRelationshipController;
use App\app\app\Http\Controllers\Api\ScenarioController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

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

        // Scenario positions
        Route::get('scenarios/{scenario}/positions', [ScenarioController::class, 'positions']);

        // Scenario metrics
        Route::get('scenarios/{scenario}/metrics', [ScenarioController::class, 'metrics']);
        Route::post('scenarios/{scenario}/calculate-metrics', [ScenarioController::class, 'calculateMetrics']);

        // Compare scenarios
        Route::get('compare-scenarios', [ScenarioController::class, 'compareScenarios']);
    });
});
