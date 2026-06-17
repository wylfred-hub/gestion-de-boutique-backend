<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    // ─── Champs autorisés à la création ───────────────
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    // ─── Champs cachés dans les réponses JSON ─────────
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ─── Casting des types ────────────────────────────
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // ─── Constantes des rôles ─────────────────────────
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN       = 'admin';
    const ROLE_VENDEUR     = 'vendeur';

    // ─── Helpers de vérification de rôle ─────────────
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->isSuperAdmin();
    }

    public function isVendeur(): bool
    {
        return $this->role === self::ROLE_VENDEUR;
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    // ─── Relations ────────────────────────────────────

    /**
     * Les organisations de l'utilisateur (Many-to-Many)
     */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class)
                    ->withPivot('role', 'is_active')
                    ->withTimestamps();
    }

    /**
     * L'organisation actuellement sélectionnée (peut être stockée en session)
     */
    public function currentOrganization()
    {
        $orgId = session('current_organization_id');
        return $orgId ? Organization::find($orgId) : null;
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // ─── Helpers ──────────────────────────────────────

    /**
     * Vérifie si l'utilisateur a un rôle dans une organisation
     */
    public function hasRoleInOrganization(Organization|int $organization, string|array $role): bool
    {
        $orgId = $organization instanceof Organization ? $organization->id : $organization;
        
        $pivot = $this->organizations()->wherePivot('organization_id', $orgId)->first()?->pivot;
        
        if (!$pivot) {
            return false;
        }

        if (is_array($role)) {
            return in_array($pivot->role, $role);
        }

        return $pivot->role === $role;
    }

    /**
     * Obtient le rôle de l'utilisateur dans une organisation
     */
    public function getRoleInOrganization(Organization|int $organization): ?string
    {
        $orgId = $organization instanceof Organization ? $organization->id : $organization;
        
        return $this->organizations()->wherePivot('organization_id', $orgId)->first()?->pivot->role;
    }

    /**
     * Vérifie si l'utilisateur est propriétaire d'une organisation
     */
    public function isOwnerOfOrganization(Organization|int $organization): bool
    {
        return $this->hasRoleInOrganization($organization, 'owner');
    }

    /**
     * Vérifie si l'utilisateur est admin dans une organisation
     */
    public function isAdminOfOrganization(Organization|int $organization): bool
    {
        return $this->hasRoleInOrganization($organization, ['owner', 'admin']);
    }
}