<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');

        // Autoriser ton frontend local; ajoute aussi d'autres origins si besoin.
        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:5173/',
        ];

        if ($origin && in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Organization-ID');
        header('Access-Control-Max-Age: 86400');

        // Répondre directement au preflight
        if ($request->getMethod() === 'OPTIONS') {
            return response()->noContent(204);
        }

        return $next($request);
    }
}

