<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
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
            'format' => 'required|string|in:pdf,xlsx,csv',
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'format.required' => 'O formato do relatório é obrigatório.',
            'format.in' => 'Formato inválido. Use pdf, xlsx ou csv.',
            'date_from.date' => 'A data inicial deve ser uma data válida.',
            'date_from.before_or_equal' => 'A data inicial deve ser anterior ou igual à data final.',
            'date_to.date' => 'A data final deve ser uma data válida.',
            'date_to.after_or_equal' => 'A data final deve ser posterior ou igual à data inicial.',
        ];
    }
}
