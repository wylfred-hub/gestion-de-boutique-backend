<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'type_libelle'    => $this->type_libelle,
            'quantity'        => $this->quantity,
            'quantity_before' => $this->quantity_before,
            'quantity_after'  => $this->quantity_after,


            // ─── Produit lié ──────────────────────────
            'product' => $this->when(
                $this->relationLoaded('product'),
                fn() => [
                    'id'        => $this->product?->id,
                    'name'      => $this->product?->name,
                    'reference' => $this->product?->reference,
                    'unit'      => $this->product?->unit,
                ]
            ),

            // ─── Utilisateur lié ──────────────────────
            'user' => $this->when(
                $this->relationLoaded('user'),
                fn() => [
                    'id'   => $this->user?->id,
                    'name' => $this->user?->name,
                    'role' => $this->user?->role,
                ]
            ),

            // ─── Référence liée (vente etc.) ──────────
            'reference' => $this->when(
                $this->reference_id,
                fn() => [
                    'id'   => $this->reference_id,
                    'type' => $this->reference_type,
                ]
            ),

            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
