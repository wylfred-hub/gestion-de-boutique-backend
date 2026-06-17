<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;

class SaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.sale_item_id'   => ['required', 'integer', 'exists:sale_items,id'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
            'reason'                 => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                  => 'Au moins un article est requis pour le retour.',
            'items.*.sale_item_id.required'   => 'L\'article est obligatoire.',
            'items.*.sale_item_id.exists'     => 'Un article sélectionné est introuvable.',
            'items.*.quantity.required'       => 'La quantité retournée est obligatoire.',
            'items.*.quantity.min'            => 'La quantité doit être au moins 1.',
            'reason.required'                 => 'Le motif du retour est obligatoire.',
        ];
    }
}