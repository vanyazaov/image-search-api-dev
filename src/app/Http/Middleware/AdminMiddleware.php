<?php
// app/Http/Middleware/AdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем что пользователь аутентифицирован и является администратором
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Access denied. Administrator privileges required.');
        }

        return $next($request);
    }
}
