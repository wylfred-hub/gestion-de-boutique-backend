<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        $orgId = session('current_organization_id');
        // Autorise si SuperAdmin global OU Admin/Owner de l'organisation actuelle
        return $user->isSuperAdmin() || $user->isAdminOfOrganization($orgId);
    }

    public function rules(): array
    {
        return [
            'product_id'   => [
                'required', 
                'integer', 
                Rule::exists('products', 'id')->where('organization_id', session('current_organization_id'))
            ],
            'new_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'   => 'Le produit est obligatoire.',
            'product_id.exists'     => 'Le produit sélectionné est introuvable.',
            'new_quantity.required' => 'La nouvelle quantité est obligatoire.',
            'new_quantity.integer'  => 'La quantité doit être un nombre entier.',
'new_quantity.min'      => 'La quantité ne peut pas être négative.',
        ];
    }
}