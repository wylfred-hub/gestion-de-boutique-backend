<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'category_id',
        'name',
        'reference',
        'description',
        'purchase_price',
        'selling_price',
        'unit',
        'barcode',
        'image',
        'stock_quantity',
        'stock_alert',
        'is_active',
    ];

    protected $casts = [
        'purchase_price'  => 'decimal:2',
        'selling_price'   => 'decimal:2',
        'stock_quantity'  => 'integer',
        'stock_alert'     => 'integer',
        'is_active'       => 'boolean',
    ];

    // ─── Auto-génération de la référence ──────────────
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->reference)) {
                $product->reference = self::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        $prefix = 'PRD';
        $year   = date('Y');
        $count  = self::forCurrentOrganization()->withTrashed()->count() + 1;
        return $prefix . '-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    // ─── Relations ────────────────────────────────────

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    // ─── Scopes ───────────────────────────────────────

    // Produits actifs seulement
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Produits en alerte stock bas
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'stock_alert');
    }

    // Recherche par nom ou référence
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('reference', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%");
        });
    }

    // ─── Helpers ──────────────────────────────────────
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->stock_alert;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock_quantity <= 0;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;
        return asset('storage/' . $this->image);
    }
}