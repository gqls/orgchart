<?php
// app/Http/Middleware/LogRequests.php
namespace App\Http\Middleware;


class VerifyCsrfToken extends \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken
{
    protected $except = [
        'api/*',
        'sanctum/csrf-cookie'
    ];
}

