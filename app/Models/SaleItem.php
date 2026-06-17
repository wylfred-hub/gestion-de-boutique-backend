<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_type',
        'discount_value',
        'total_price',
    ];

    protected $casts = [
        'quantity'       => 'integer',
        'unit_price'     => 'decimal:2',
        'discount_value' => 'decimal:2',
        'total_price'    => 'decimal:2',
    ];

    // ─── Relations ────────────────────────────────────
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ─── Helpers ──────────────────────────────────────
    public function calculerTotal(): float
    {
        $subtotal = $this->quantity * $this->unit_price;

        if (!$this->discount_type || !$this->discount_value) {
            return $subtotal;
        }

        if ($this->discount_type === Sale::DISCOUNT_POURCENTAGE) {
            return $subtotal - ($subtotal * $this->discount_value / 100);
        }

        return max(0, $subtotal - $this->discount_value);
    }
}