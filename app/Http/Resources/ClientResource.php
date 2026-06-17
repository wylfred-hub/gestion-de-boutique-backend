<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'full_name'    => $this->full_name,
            'first_name'   => $this->first_name,
            'last_name'    => $this->last_name,
            'company_name' => $this->company_name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'address'      => $this->address,
            'category'     => $this->category,
            'is_vip'       => $this->isVip(),
            'notes'        => $this->notes,

            // ─── Statistiques ─────────────────────────
            'total_achats' => $this->when(
                isset($this->total_achats),
                fn() => (float) $this->total_achats
            ),
            'sales_count'  => $this->when(
                isset($this->sales_count),
                $this->sales_count
            ),

            // ─── Historique ventes ────────────────────
            'sales' => $this->when(
                $this->relationLoaded('sales'),
                fn() => SaleResource::collection($this->sales)
            ),

            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}