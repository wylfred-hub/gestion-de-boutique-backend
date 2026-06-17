<?php

namespace App\Http\Requests\Client;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole(['admin', 'vendeur']);
    }

    public function rules(): array
    {
        $orgId = auth()->user()->organization_id;

        return [
            'type'         => ['required', Rule::in([
                Client::TYPE_PARTICULIER,
                Client::TYPE_ENTREPRISE,
            ])],
            'first_name'   => ['required_if:type,particulier', 'nullable', 'string', 'max:100'],
            'last_name'    => ['required_if:type,particulier', 'nullable', 'string', 'max:100'],
            'company_name' => ['required_if:type,entreprise',  'nullable', 'string', 'max:150'],
            'email'        => [
                'nullable',
                'email',
                'max:150',
                // Unicité email par organisation (soft delete respecté)
                Rule::unique('clients', 'email')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'phone'    => ['nullable', 'string', 'max:30'],
            'address'  => ['nullable', 'string'],
            'category' => ['nullable', Rule::in([
                Client::CAT_STANDARD,
                Client::CAT_VIP,
            ])],
            'notes'    => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'            => 'Le type de client est obligatoire.',
            'type.in'                  => 'Le type doit être particulier ou entreprise.',
            'first_name.required_if'   => 'Le prénom est obligatoire pour un particulier.',
            'last_name.required_if'    => 'Le nom est obligatoire pour un particulier.',
            'company_name.required_if' => 'La raison sociale est obligatoire pour une entreprise.',
            'email.email'              => 'L\'adresse email n\'est pas valide.',
            'email.unique'             => 'Cette adresse email est déjà utilisée dans votre organisation.',
            'category.in'              => 'La catégorie doit être standard ou vip.',
        ];
    }
}