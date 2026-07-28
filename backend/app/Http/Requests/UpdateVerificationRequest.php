<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('afericoes.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:2000',
            'params' => 'nullable|array',
            'params.*' => [
                'numeric',
                'regex:/^-?\d+(\.\d+)?$/',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notes.max' => 'As observações não podem exceder 2000 caracteres.',
            'params.*.numeric' => 'Cada valor de parâmetro deve ser numérico.',
            'params.*.regex' => 'Cada valor de parâmetro deve ser um número válido.',
        ];
    }
}
