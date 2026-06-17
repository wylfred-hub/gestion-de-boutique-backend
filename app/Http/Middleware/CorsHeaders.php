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

        // IMPORTANT: La réponse au preflight (OPTIONS) doit inclure Access-Control-Allow-Origin.
        // Sinon le navigateur bloque la requête.
        $isAllowedOrigin = $origin && in_array($origin, $allowedOrigins, true);
        if ($isAllowedOrigin) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Organization-ID');
        header('Access-Control-Max-Age: 86400');

        // Répondre directement au preflight
        if ($request->getMethod() === 'OPTIONS') {
            // Si Origin n'est pas dans la liste, on renvoie quand même les headers méthodes/headers
            // (ça aide au debug), mais Access-Control-Allow-Origin manquera et sera bloquant.
            // D'où le fait de whitelist ci-dessus.
            return response()->noContent(204);
        }

        return $next($request);

    }
}

