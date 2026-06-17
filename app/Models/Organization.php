<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
        'logo',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    

    // ─── Relations ────────────────────────────────────

    /**
     * Les utilisateurs de l'organisation
     */
    public function users()
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('role', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Les catégories de l'organisation
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Les produits de l'organisation
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Les clients de l'organisation
     */
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Les ventes de l'organisation
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Les mouvements de stock de l'organisation
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
