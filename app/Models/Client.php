<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'type',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'address',
        'category',
        'notes',
    ];

    // ─── Constantes ───────────────────────────────────
    const TYPE_PARTICULIER = 'particulier';
    const TYPE_ENTREPRISE  = 'entreprise';
    const CAT_STANDARD     = 'standard';
    const CAT_VIP          = 'vip';

    // ─── Relations ────────────────────────────────────
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    // ─── Helpers ──────────────────────────────────────
    public function getFullNameAttribute(): string
    {
        if ($this->type === self::TYPE_ENTREPRISE) {
            return $this->company_name ?? '';
        }
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isVip(): bool
    {
        return $this->category === self::CAT_VIP;
    }

    public function getTotalAchatsAttribute(): float
    {
        return $this->sales()
            ->where('status', Sale::STATUS_LIVREE)
            ->sum('total_amount');
    }

    // ─── Scopes ───────────────────────────────────────
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name',   'like', "%{$search}%")
              ->orWhere('last_name',    'like', "%{$search}%")
              ->orWhere('company_name', 'like', "%{$search}%")
              ->orWhere('email',        'like', "%{$search}%")
              ->orWhere('phone',        'like', "%{$search}%");
        });
    }

    public function scopeVip($query)
    {
        return $query->where('category', self::CAT_VIP);
    }
}