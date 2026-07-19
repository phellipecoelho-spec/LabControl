<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'remember' => ['boolean', 'nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'Credenciais inválidas.',
        ];
    }
}
