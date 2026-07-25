<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteCalibrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'completed_at' => 'nullable|date',
            'certificate_number' => 'nullable|string|max:100',
            'responsible' => 'nullable|string|max:255',
            'laboratory' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
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
            'completed_at.date' => 'A data de conclusão deve ser uma data válida.',
            'certificate_number.max' => 'O número do certificado não pode exceder 100 caracteres.',
            'responsible.max' => 'O responsável não pode exceder 255 caracteres.',
            'laboratory.max' => 'O laboratório não pode exceder 255 caracteres.',
            'notes.max' => 'As observações não podem exceder 2000 caracteres.',
        ];
    }
}
