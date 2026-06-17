<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                // Unicité du nom par organisation, en ignorant la catégorie en cours
                Rule::unique('categories', 'name')
                    ->where('organization_id', auth()->user()->organization_id)
                    ->ignore($categoryId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ce nom de catégorie existe déjà dans votre organisation.',
            'name.max'    => 'Le nom ne peut pas dépasser 100 caractères.',
        ];
    }
}