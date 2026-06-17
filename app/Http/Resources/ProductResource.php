<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'organizationId'  => $this->organization_id,
            'categoryId'      => (string) $this->category_id,
            'name'            => $this->name,
            'reference'       => $this->reference,
            'description'     => $this->description,
            'purchasePrice'   => (float) $this->purchase_price,
            'sellingPrice'    => (float) $this->selling_price,
            'unit'            => $this->unit,
            'barcode'         => $this->barcode,
            'image'           => $this->image_url,
            'stockQuantity'   => (int) $this->stock_quantity,
            'stockAlert'      => (int) $this->stock_alert,
            'isActive'        => $this->is_active,
            'isLowStock'      => $this->isLowStock(),
            'isOutOfStock'    => $this->isOutOfStock(),

            // Alias conservés pour compatibilité frontend existant
            'price'           => (float) $this->selling_price,
            'alertThreshold'  => (int) $this->stock_alert,

            'category'        => $this->when(
                $this->relationLoaded('category'),
                fn() => [
                    'id'   => $this->category?->id,
                    'name' => $this->category?->name,
                ]
            ),
            'created_at'  => $this->created_at?->format('d/m/Y H:i'),
            'updated_at'  => $this->updated_at?->format('d/m/Y H:i'),
            'deleted_at'  => $this->when(
                $this->deleted_at,
                $this->deleted_at?->format('d/m/Y H:i')
            ),
        ];
    }
}
