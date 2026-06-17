<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                // Unicité du nom par organisation
                Rule::unique('categories', 'name')
                    ->where('organization_id', auth()->user()->organization_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.max'      => 'Le nom ne peut pas dépasser 100 caractères.',
            'name.unique'   => 'Ce nom de catégorie existe déjà dans votre organisation.',
        ];
    }
}