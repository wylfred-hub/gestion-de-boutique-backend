<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'client_id',
        'user_id',
        'sale_number',
        'status',
        'discount_type',
        'discount_value',
        'subtotal',
        'total_amount',
        'notes',
        'delivered_at',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'subtotal'       => 'decimal:2',
        'total_amount'   => 'decimal:2',
        'delivered_at'   => 'datetime',
    ];

    // ─── Constantes statuts ───────────────────────────
    const STATUS_ENCOURS      = 'encours';
    const STATUS_CONFIRMEE      = 'confirmee';
    const STATUS_ANNULEE        = 'annulee';

    const STATUTS = [
        self::STATUS_ENCOURS,
        self::STATUS_CONFIRMEE,
        self::STATUS_ANNULEE,
    ];

    const TRANSITIONS = [
        self::STATUS_ENCOURS   => [self::STATUS_CONFIRMEE, self::STATUS_ANNULEE],
        self::STATUS_CONFIRMEE => [self::STATUS_ANNULEE],
        self::STATUS_ANNULEE   => [],
    ];

    // ─── Constantes remises ───────────────────────────
    const DISCOUNT_FIXE       = 'fixe';
    const DISCOUNT_POURCENTAGE = 'pourcentage';

    // ─── Relations ────────────────────────────────────
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    // ─── Helpers ──────────────────────────────────────
    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowed);
    }

    public function isAnnulee(): bool
    {
        return $this->status === self::STATUS_ANNULEE;
    }

    // public function isLivree(): bool
    // {
    //     return $this->status === self::STATUS_LIVREE;
    // }

    public function isBrouillon(): bool
    {
        return $this->status === self::STATUS_ENCOURS;
    }

    public function getStatusLibelleAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ENCOURS      => 'En cours',
            self::STATUS_CONFIRMEE      => 'Confirmée',
            self::STATUS_ANNULEE        => 'Annulée',
            default                     => 'Inconnu',
        };
    }

    // ─── Scopes ───────────────────────────────────────
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [
            $from . ' 00:00:00',
            $to   . ' 23:59:59',
        ]);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('sale_number', 'like', "%{$search}%")
                ->orWhereHas(
                    'client',
                    fn($c) =>
                    $c->where('first_name',   'like', "%{$search}%")
                        ->orWhere('last_name',    'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                );
        });
    }
}
