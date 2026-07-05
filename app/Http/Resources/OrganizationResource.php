<?php

// namespace App\Http\Resources;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;

// /**
//  * @mixin \App\Models\Organization
//  */
// class OrganizationResource extends JsonResource
// {
//     /**
//      * Transform the resource into an array.
//      */
//     public function toArray(Request $request): array
//     {
//         return [
//             'id' => $this->id,
//             'name' => $this->name,
//             'email' => $this->email,
//             'phone' => $this->phone,
//             'address' => $this->address,
//             'city' => $this->city,
//             'postal_code' => $this->postal_code,
//             'country' => $this->country,
//             'description' => $this->description,
//             'logo' => $this->logo,
//             'is_active' => $this->is_active,
//             'createdAt' => $this->created_at?->toISOString(),
//             'updatedAt' => $this->updated_at?->toISOString(),
//         ];
//     }
// }

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Organization
 */
class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'description' => $this->description,
            'logo' => $this->logo,
            'is_active' => $this->is_active,
            // rôle de l'utilisateur connecté DANS cette organisation
            'role' => $this->whenPivotLoaded('organization_user', function () {
                return $this->pivot->role;
            }),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}