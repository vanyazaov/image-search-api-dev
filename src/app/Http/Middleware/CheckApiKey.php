<?php
// app/Http/Middleware/CheckApiKey.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->get('api_key');

        if (!$apiKey) {
            return response()->json(['error' => 'API key required'], 401);
        }

        $user = User::findByApiKey($apiKey);

        if (!$user || !$user->isValidSubscription()) {
            return response()->json(['error' => 'Invalid or expired API key'], 403);
        }

        // Привязываем пользователя к запросу
        $request->attributes->set('api_user', $user);

        return $next($request);
    }
}
