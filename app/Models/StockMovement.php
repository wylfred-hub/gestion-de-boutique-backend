<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',

        'reference_id',
        'reference_type',
    ];

    protected $casts = [
        'quantity'        => 'integer',
        'quantity_before' => 'integer',
        'quantity_after'  => 'integer',
        'reference_id'    => 'integer',
    ];

    // ─── Constantes des types ─────────────────────────
    const TYPE_ENTREE      = 'entree';
    const TYPE_SORTIE      = 'sortie';
    const TYPE_AJUSTEMENT  = 'ajustement';
    const TYPE_RETOUR      = 'retour';

    const TYPES = [
        self::TYPE_ENTREE,
        self::TYPE_SORTIE,
        self::TYPE_AJUSTEMENT,
        self::TYPE_RETOUR,
    ];

    // ─── Relations ────────────────────────────────────
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ───────────────────────────────────────
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [
            $from . ' 00:00:00',
            $to . ' 23:59:59',
        ]);
    }

    // ─── Helpers ──────────────────────────────────────
    public function isEntree(): bool
    {
        return $this->type === self::TYPE_ENTREE;
    }

    public function isSortie(): bool
    {
        return $this->type === self::TYPE_SORTIE;
    }

    public function isAjustement(): bool
    {
        return $this->type === self::TYPE_AJUSTEMENT;
    }

    public function isRetour(): bool
    {
        return $this->type === self::TYPE_RETOUR;
    }

    public function getTypeLibelleAttribute(): string
    {
        return match($this->type) {
            self::TYPE_ENTREE     => 'Entrée',
            self::TYPE_SORTIE     => 'Sortie',
            self::TYPE_AJUSTEMENT => 'Ajustement',
            self::TYPE_RETOUR     => 'Retour',
            default               => 'Inconnu',
        };
    }
}