<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'organization_id'=> $this->organization_id,
            'products_count' => $this->whenCounted('products'),
            'created_at'     => $this->created_at?->format('d/m/Y H:i'),
            'updated_at'     => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}