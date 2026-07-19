<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'Permissões são obrigatórias.',
            'permissions.array' => 'Permissões devem ser uma lista.',
            'permissions.*.exists' => 'Permissão informada não existe.',
        ];
    }
}
