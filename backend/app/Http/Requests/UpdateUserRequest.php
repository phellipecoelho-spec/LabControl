<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->user;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required_with:password'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['exists:roles,id'],
            'is_active' => ['sometimes', 'boolean'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Informe um email válido.',
            'email.unique' => 'Este email já está em uso.',
            'password.min' => 'Senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'Confirmação de senha não confere.',
            'password_confirmation.required_with' => 'Confirmação de senha é obrigatória.',
            'roles.*.exists' => 'Perfil informado não existe.',
            'is_active.boolean' => 'Status deve ser ativo ou inativo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('password') && empty($this->password)) {
            $this->request->remove('password');
            $this->request->remove('password_confirmation');
        }
    }
}
