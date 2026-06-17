<?php

namespace App\Http\Requests\Sale;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return $this->user()->hasRole(['admin', 'vendeur']);
        return $this->header('X-Organization-ID') ?? session('current_organization_id');
    }

    public function rules(): array
    {
        $orgId = session('current_organization_id') ?? $this->user()->organizations()->first()?->id;

        return [
            'client_id' => [
                'nullable',
                'integer',
                // Le client doit appartenir à l'organisation
                Rule::exists('clients', 'id')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'discount_type'  => ['nullable', Rule::in([Sale::DISCOUNT_FIXE, Sale::DISCOUNT_POURCENTAGE])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string'],

            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => [
                'required',
                'integer',
                // Le produit doit appartenir à l'organisation
                Rule::exists('products', 'id')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.discount_type'  => ['nullable', Rule::in([Sale::DISCOUNT_FIXE, Sale::DISCOUNT_POURCENTAGE])],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists'              => 'Le client sélectionné est introuvable dans votre organisation.',
            'items.required'                => 'La vente doit contenir au moins un produit.',
            'items.min'                     => 'La vente doit contenir au moins un produit.',
            'items.*.product_id.required'   => 'Le produit est obligatoire.',
            'items.*.product_id.exists'     => 'Un produit sélectionné est introuvable dans votre organisation.',
            'items.*.quantity.required'     => 'La quantité est obligatoire.',
            'items.*.quantity.min'          => 'La quantité doit être au moins 1.',
            'items.*.unit_price.required'   => 'Le prix unitaire est obligatoire.',
            'items.*.unit_price.min'        => 'Le prix unitaire ne peut pas être négatif.',
            'discount_type.in'             => 'Le type de remise doit être fixe ou pourcentage.',
            'items.*.discount_type.in'     => 'Le type de remise doit être fixe ou pourcentage.',
        ];
    }
}