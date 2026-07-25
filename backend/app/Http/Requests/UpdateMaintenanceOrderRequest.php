<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceOrderRequest extends FormRequest
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
            'assigned_to' => 'nullable|string|exists:users,id',
            'priority' => 'nullable|string|in:low,medium,high,critical',
            'description' => 'nullable|string|max:5000',
            'scheduled_date' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
            'interval_value' => 'nullable|integer|min:1',
            'interval_unit' => 'nullable|string|in:days,months,hours',
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
            'assigned_to.exists' => 'O usuário selecionado é inválido.',
            'priority.in' => 'A prioridade deve ser baixa, média, alta ou crítica.',
            'description.max' => 'A descrição não pode exceder 5000 caracteres.',
            'notes.max' => 'As observações não podem exceder 5000 caracteres.',
            'interval_value.integer' => 'O valor do intervalo deve ser um número inteiro.',
            'interval_value.min' => 'O valor do intervalo deve ser no mínimo 1.',
            'interval_unit.in' => 'A unidade do intervalo deve ser meses, dias ou horas.',
        ];
    }
}
