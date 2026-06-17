<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour gérer l'organisation courante
 * 
 * S'assure que:
 * 1. L'utilisateur authentifié a au moins une organisation
 * 2. L'organisation courante en session existe et est accessible
 * 3. Redirige vers la sélection d'organisation si nécessaire
 */
class EnsureOrganizationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ignorer les routes publiques
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Le Super Admin a un accès global. On ne lui force pas d'organisation 
        // par défaut pour les vues, mais il en faut une pour les créations.
        $isSuperAdmin = $user->role === 'super_admin' || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());

        // Priorité au Header, puis à la session
        $currentOrgId = $request->header('X-Organization-ID') ?: session('current_organization_id');

        // Optimisation : On cache les IDs d'organisations autorisées en session pour éviter de requêter la DB à chaque appel
        $authorizedOrgIds = session('authorized_organization_ids');
        $firstOrgId = session('first_organization_id');

        if (!$authorizedOrgIds) {
            if ($isSuperAdmin) {
                // Un Super Admin peut accéder à toutes les organisations actives
                $organizations = \App\Models\Organization::where('is_active', true)->get();
            } else {
                $organizations = $user->organizations()->wherePivot('is_active', true)->get();
            }
            
            $authorizedOrgIds = $organizations->pluck('id')->toArray();
            $firstOrgId = $organizations->first()?->id;

            // Stockage en session pour les requêtes suivantes
            session([
                'authorized_organization_ids' => $authorizedOrgIds,
                'first_organization_id' => $firstOrgId
            ]);
        }

        // Si l'utilisateur n'a aucune organisation
        if (empty($authorizedOrgIds)) {
            // Pas de logout ici : on veut une erreur JSON 403 pour les appels API.
            return response()->json([
                'message' => 'Accès refusé. Vous n\'êtes rattaché à aucune organisation.'
            ], 403);
        }

        // Si pas d'organisation identifiée, on prend la première par défaut
        if (!$currentOrgId) {
            $currentOrgId = $firstOrgId;
        }

        // Vérifier que l'organisation courante est accessible
        if (!in_array($currentOrgId, $authorizedOrgIds)) {
            $currentOrgId = $firstOrgId;
        }

        // On stocke en session pour les composants qui en dépendent encore
        session(['current_organization_id' => $currentOrgId]);

        // Injecter l'ID dans la requête pour satisfaire la validation (422) 
        // même si le champ n'est pas présent dans le payload JSON envoyé par le frontend.
        if ($currentOrgId && !$request->has('organization_id')) {
            $request->merge(['organization_id' => $currentOrgId]);
        }

        return $next($request);
    }
}
