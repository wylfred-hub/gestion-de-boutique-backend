<?php

namespace App\Http\Requests\Sale;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole([
            'admin',
            'vendeur',
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Sale::STATUTS)],
            'notes'  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status.in'       => 'Statut invalide. Valeurs acceptées : '
                . implode(', ', Sale::STATUTS),
        ];
    }
}