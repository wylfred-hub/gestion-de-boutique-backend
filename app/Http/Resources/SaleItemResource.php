<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'quantity'       => $this->quantity,
            'unit_price'     => (float) $this->unit_price,
            'discount_type'  => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'total_price'    => (float) $this->total_price,

            // ─── Produit lié ──────────────────────────
            'product' => $this->when(
                $this->relationLoaded('product'),
                fn() => [
                    'id'        => $this->product?->id,
                    'name'      => $this->product?->name,
                    'reference' => $this->product?->reference,
                    'unit'      => $this->product?->unit,
                    'image_url' => $this->product?->image_url,
                ]
            ),

            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}