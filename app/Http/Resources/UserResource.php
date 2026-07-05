<?php

// namespace App\Http\Resources;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;

// class UserResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return [
//             'id'         => $this->id,
//             'name'       => $this->name,
//             'email'      => $this->email,
//             'role'       => $this->role,
//             'is_active'  => $this->is_active,
//             'created_at' => $this->created_at?->format('d/m/Y H:i'),
//             'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
//         ];
//     }
// }


// namespace App\Http\Resources;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;

// class UserResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return [
//             'id'         => $this->id,
//             'name'       => $this->name,
//             'email'      => $this->email,
//             'role'       => $this->whenPivotLoaded('organization_user', function () {
//                 return $this->pivot->role;
//             }, $this->role), // fallback si pas de pivot chargée
//             'is_active'  => $this->whenPivotLoaded('organization_user', function () {
//                 return (bool) $this->pivot->is_active;
//             }, $this->is_active),
//             'created_at' => $this->created_at?->format('d/m/Y H:i'),
//             'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
//         ];
//     }
// }


namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            // rôle global : uniquement pertinent pour super_admin
            'is_super_admin' => $this->isSuperAdmin(),
            // rôle contextuel : seulement si la pivot d'une organisation est chargée
            'role'       => $this->whenPivotLoaded('organization_user', function () {
                return $this->pivot->role;
            }),
            'is_active'  => $this->whenPivotLoaded('organization_user', function () {
                return (bool) $this->pivot->is_active;
            }, $this->is_active),
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}