<?php

namespace App\Http\Requests\Sale;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return auth()->user()->hasRole(['admin', 'vendeur']);
        return $this->header('X-Organization-ID') ?? session('current_organization_id');
    }

    public function rules(): array
    {
        $orgId = $this->header('X-Organization-ID') ?? session('current_organization_id');

        return [
            'client_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('clients', 'id')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'discount_type'  => ['sometimes', 'nullable', Rule::in([Sale::DISCOUNT_FIXE, Sale::DISCOUNT_POURCENTAGE])],
            'discount_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes'          => ['sometimes', 'nullable', 'string'],

            'items'                  => ['sometimes', 'array', 'min:1'],
            'items.*.product_id'     => [
                'required_with:items',
                'integer',
                Rule::exists('products', 'id')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'items.*.quantity'       => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price'     => ['required_with:items', 'numeric', 'min:0'],
            'items.*.discount_type'  => ['nullable', Rule::in([Sale::DISCOUNT_FIXE, Sale::DISCOUNT_POURCENTAGE])],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists'            => 'Le client sélectionné est introuvable dans votre organisation.',
            'items.min'                   => 'La vente doit contenir au moins un produit.',
            'items.*.product_id.exists'   => 'Un produit sélectionné est introuvable dans votre organisation.',
            'items.*.quantity.min'        => 'La quantité doit être au moins 1.',
            'items.*.unit_price.min'      => 'Le prix unitaire ne peut pas être négatif.',
        ];
    }
}
