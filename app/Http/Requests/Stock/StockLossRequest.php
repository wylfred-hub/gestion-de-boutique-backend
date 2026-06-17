<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockLossRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        $orgId = session('current_organization_id') ?? $user->organizations()->first()?->id;

        return $user->isSuperAdmin() || $user->isAdminOfOrganization($orgId);
    }

    public function rules(): array
    {
        $orgId = session('current_organization_id') ?? $this->user()->organizations()->first()?->id;

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('organization_id', $orgId)
            ],
            'quantity'   => ['required', 'integer', 'min:1'],
            // reason n’est plus transmis depuis le front

        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Le produit est obligatoire.',
            'product_id.exists'   => 'Le produit sélectionné est introuvable.',
            'quantity.required'   => 'La quantité est obligatoire.',
            'quantity.integer'    => 'La quantité doit être un nombre entier.',
            'quantity.min'        => 'La quantité doit être au moins 1.',
        ];
    }
}