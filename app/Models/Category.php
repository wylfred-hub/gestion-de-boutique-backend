<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
    ];

    // ─── Relations ────────────────────────────────────

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // ─── Helpers ──────────────────────────────────────

    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }
}