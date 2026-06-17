<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin(); // admin seulement
    }

    public function rules(): array
    {
        $userId = $this->route('user'); // ID depuis l'URL

        return [
            'name'      => ['sometimes', 'string', 'max:100'],
            'email'     => ['sometimes', 'email', 'max:150', Rule::unique('users')->ignore($userId)],
            'password'  => ['sometimes', 'string', 'min:6', 'confirmed'],
            'role'      => ['sometimes', Rule::in([
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMIN,
                User::ROLE_VENDEUR,
            ])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'       => 'Le nom ne peut pas dépasser 100 caractères.',
            'email.email'    => 'L\'adresse email n\'est pas valide.',
            'email.unique'   => 'Cette adresse email est déjà utilisée.',
            'password.min'   => 'Le mot de passe doit contenir au moins 6 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.in'        => 'Le rôle doit être : super_admin, admin ou vendeur.',
        ];
    }
}