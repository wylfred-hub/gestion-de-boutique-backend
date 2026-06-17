<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // ─── Vérifier que l'utilisateur est connecté ──
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }

        // ─── Vérifier que le compte est actif ─────────
        if (!$request->user()->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est désactivé. Contactez l\'administrateur.',
            ], 403);
        }

        // ─── Vérifier le rôle ─────────────────────────
        $user = $request->user();

        // Le super_admin a un accès illimité par défaut
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        if (!empty($roles)) {
            // Vérification sécurisée : utilise hasRole() si elle existe, sinon compare directement
            $hasPermission = method_exists($user, 'hasRole') 
                ? $user->hasRole($roles) 
                : in_array($user->role, $roles);

            if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Vous n\'avez pas les permissions nécessaires.',
                'your_role'     => $request->user()->role,
                'required_roles' => $roles,
            ], 403);
            }
        }

        return $next($request);
    }
}