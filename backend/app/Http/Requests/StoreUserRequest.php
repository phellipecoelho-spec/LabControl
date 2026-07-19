<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nome é obrigatório.',
            'email.required' => 'Email é obrigatório.',
            'email.email' => 'Informe um email válido.',
            'email.unique' => 'Este email já está em uso.',
            'password.required' => 'Senha é obrigatória.',
            'password.min' => 'Senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'Confirmação de senha não confere.',
            'password_confirmation.required' => 'Confirmação de senha é obrigatória.',
            'roles.*.exists' => 'Perfil informado não existe.',
        ];
    }
}
