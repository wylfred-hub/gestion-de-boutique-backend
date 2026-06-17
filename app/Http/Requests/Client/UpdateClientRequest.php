<?php

namespace App\Http\Requests\Client;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole(['admin', 'vendeur']);
    }

    public function rules(): array
    {
        $clientId = $this->route('id');
        $orgId    = auth()->user()->organization_id;

        return [
            'type'         => ['sometimes', Rule::in([
                Client::TYPE_PARTICULIER,
                Client::TYPE_ENTREPRISE,
            ])],
            'first_name'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'email'        => [
                'sometimes',
                'nullable',
                'email',
                'max:150',
                Rule::unique('clients', 'email')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at')
                    ->ignore($clientId),
            ],
            'phone'    => ['sometimes', 'nullable', 'string', 'max:30'],
            'address'  => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', Rule::in([
                Client::CAT_STANDARD,
                Client::CAT_VIP,
            ])],
            'notes'    => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'      => 'Le type doit être particulier ou entreprise.',
            'email.email'  => 'L\'adresse email n\'est pas valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée dans votre organisation.',
            'category.in'  => 'La catégorie doit être standard ou vip.',
        ];
    }
}