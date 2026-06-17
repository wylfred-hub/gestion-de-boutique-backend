<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return auth()->user()->hasRole('admin');
        return $this->header('X-Organization-ID') ?? session('current_organization_id');
    }

    public function rules(): array
    {
        $productId = $this->route('id');
        $orgId = $this->header('X-Organization-ID') ?? session('current_organization_id');

        return [
            'category_id' => [
                'sometimes',
                'integer',
                Rule::exists('categories', 'id')->where('organization_id', $orgId),
            ],
            'name'           => ['sometimes', 'string', 'max:150'],
            'reference'      => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('products', 'reference')
                    ->ignore($productId)
                    ->whereNull('deleted_at'),
            ],
            'description'    => ['nullable', 'string'],
            'purchase_price' => ['sometimes', 'numeric', 'min:0'],
            'selling_price'  => ['sometimes', 'numeric', 'min:0'],
            'unit'           => ['nullable', 'string', 'max:50'],
            'barcode'        => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')
                    ->ignore($productId)
                    ->whereNull('deleted_at'),
            ],
            'image'          => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'stock_alert'    => ['sometimes', 'integer', 'min:0'],
            'is_active'      => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists'   => 'La catégorie sélectionnée est introuvable ou n\'appartient pas à votre organisation.',
            'name.max'             => 'Le nom ne peut pas dépasser 150 caractères.',
            'reference.unique'     => 'Cette référence existe déjà.',
            'purchase_price.min'   => 'Le prix d\'achat ne peut pas être négatif.',
            'selling_price.min'    => 'Le prix de vente ne peut pas être négatif.',
            'barcode.unique'       => 'Ce code-barres existe déjà.',
            'image.image'          => 'Le fichier doit être une image.',
            'image.mimes'          => 'L\'image doit être en format jpeg, png, jpg ou webp.',
            'image.max'            => 'L\'image ne peut pas dépasser 2MB.',
            'stock_alert.min'      => 'Le seuil d\'alerte ne peut pas être négatif.',
        ];
    }
}