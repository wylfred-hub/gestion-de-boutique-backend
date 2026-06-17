<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'sale_number'    => $this->sale_number,
            'status'         => $this->status,
            'status_libelle' => $this->status_libelle,
            'discount_type'  => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'subtotal'       => (float) $this->subtotal,
            'total_amount'   => (float) $this->total_amount,
            // alias compat front (front utilise sale.total)
            'total'          => (float) $this->total_amount,
            'notes'          => $this->notes,
            'delivered_at'   => $this->delivered_at?->format('d/m/Y H:i'),

            // ─── Client lié ───────────────────────────
            'client' => $this->when(
                $this->relationLoaded('client'),
                fn() => $this->client ? [
                    'id'        => $this->client->id,
                    'full_name' => $this->client->full_name,
                    'email'     => $this->client->email,
                    'phone'     => $this->client->phone,
                    'category'  => $this->client->category,
                ] : null
            ),

            // ─── Vendeur ──────────────────────────────
            'user' => $this->when(
                $this->relationLoaded('user'),
                fn() => [
                    'id'   => $this->user?->id,
                    'name' => $this->user?->name,
                    'role' => $this->user?->role,
                ]
            ),

            // ─── Articles de la vente ─────────────────
            'items' => $this->when(
                $this->relationLoaded('items'),
                fn() => SaleItemResource::collection(
                    $this->items->load('product')
                )
            ),

            // ─── Résumé items ─────────────────────────
            'items_count' => $this->when(
                isset($this->items_count),
                $this->items_count
            ),

            // ─── Transitions possibles ────────────────
            'can_transition_to' => array_values(
                \App\Models\Sale::TRANSITIONS[$this->status] ?? []
            ),

            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}