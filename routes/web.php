<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/debug-providers', function () {
    $providers = app()->getLoadedProviders();
    return response()->json([
        'route_service_provider_loaded' => isset($providers[App\Providers\RouteServiceProvider::class]),
        'all_providers' => array_keys($providers)
    ]);
});

// routes/web.php (add at the top)
Route::get('/debug-routes', function () {
    $routes = Route::getRoutes();
    $routeList = [];

    foreach ($routes as $route) {
        $routeList[] = [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
        ];
    }

    return response()->json($routeList);
});

// Debug routes
Route::get('/debug-organizations', function () {
    if (Auth::check()) {
        return response()->json([
            'user' => Auth::user(),
            'organizations' => Auth::user()->organizations,
            'is_authenticated' => true
        ]);
    } else {
        return response()->json([
            'message' => 'Not authenticated',
            'is_authenticated' => false
        ]);
    }
});

Route::get('/debug-organizations2', function () {
    $user = Auth::user();
    if ($user) {
        return [
            'authenticated' => true,
            'user' => $user,
            'organizations' => $user->organizations()->get()
        ];
    }
    return ['authenticated' => false];
});

// Make sure this is at the BOTTOM of the file - catch-all route for SPA
Route::get('{any}', function () {
    return view('app');
})->where('any', '^(?!api|debug-organizations).*');