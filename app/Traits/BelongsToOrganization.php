<?php

namespace App\Traits;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait BelongsToOrganization
 * Automatise le filtrage des modèles par organisation
 * 
 * Utilisation:
 * - Dans les contrôleurs: Product::ofOrganization()->get();
 * - Ou avec l'organisation courante: Product::forCurrentOrganization()->get();
 */
trait BelongsToOrganization
{
    /**
     * Scope: Filtrer par une organisation spécifique
     */
    public function scopeOfOrganization(Builder $query, Organization|int $organization): Builder
    {
        $orgId = $organization instanceof Organization ? $organization->id : $organization;
        return $query->where('organization_id', $orgId);
    }

    /**
     * Scope: Filtrer par l'organisation de l'utilisateur courant
     */
    public function scopeForCurrentOrganization(Builder $query): Builder
    {
        $currentOrg = $this->getCurrentOrganization();
        
        if (!$currentOrg) {
            return $query->whereNull('organization_id'); // ou retourner une requête vide
        }

        return $query->where('organization_id', $currentOrg->id);
    }

    /**
     * Obtient l'organisation courante
     */
    protected function getCurrentOrganization(): ?Organization
    {
        if (auth()->guest()) {
            return null;
        }

        // Vérifier le header en priorité, puis la session
        $orgId = request()->header('X-Organization-ID') ?: session('current_organization_id');
        
        if (!$orgId) {
            // Fallback : première organisation active assignée à l'utilisateur
            $orgId = auth()->user()->organizations()
                ->wherePivot('is_active', true)
                ->first()?->id;

            if ($orgId) {
                session(['current_organization_id' => $orgId]);
            }
        }

        return $orgId ? Organization::find($orgId) : null;
    }

    /**
     * Boot du trait: supprimer automatiquement l'organization_id null
     */
    protected static function bootBelongsToOrganization(): void
    {
        // Si une organisation courante existe, l'assigner automatiquement aux nouveaux modèles
        static::creating(function ($model) {
            if (empty($model->organization_id) && auth()->check()) {
                // Vérifier Header -> Session -> Premier accès User
                // On regarde aussi si l'ID a été injecté dans la requête par le middleware
                $orgId = request()->header('X-Organization-ID')
                    ?: request()->input('organization_id')
                    ?: session('current_organization_id') 
                    ?: auth()->user()->organizations()
                        ->wherePivot('is_active', true)
                        ->first()?->id;
                    
                if ($orgId) {
                    $model->organization_id = $orgId;
                }
            }
        });
    }
}
