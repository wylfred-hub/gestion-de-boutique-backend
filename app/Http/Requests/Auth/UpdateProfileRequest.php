<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // tout utilisateur connecté peut modifier son profil
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name'                  => ['sometimes', 'string', 'max:100'],
            'email'                 => ['sometimes', 'email', 'max:150', Rule::unique('users')->ignore($userId)],
            'current_password'      => ['required_with:new_password', 'string'],
            'new_password'          => ['sometimes', 'string', 'min:6', 'confirmed'],
            'new_password_confirmation' => ['required_with:new_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max'                       => 'Le nom ne peut pas dépasser 100 caractères.',
            'email.email'                    => 'L\'adresse email n\'est pas valide.',
            'email.unique'                   => 'Cette adresse email est déjà utilisée.',
            'current_password.required_with' => 'Le mot de passe actuel est obligatoire pour le modifier.',
            'new_password.min'               => 'Le nouveau mot de passe doit contenir au moins 6 caractères.',
            'new_password.confirmed'         => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}