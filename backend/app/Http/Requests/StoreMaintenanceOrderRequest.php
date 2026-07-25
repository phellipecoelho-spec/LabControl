<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceOrderRequest extends FormRequest
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
            'equipment_id' => 'required|string|exists:equipments,id',
            'type' => 'required|string|in:preventive,corrective',
            'priority' => 'required|string|in:low,medium,high,critical',
            'description' => 'required|string|max:5000',
            'scheduled_date' => 'nullable|date|after_or_equal:today',
            'interval_value' => 'required_if:type,preventive|integer|min:1|nullable',
            'interval_unit' => 'required_if:type,preventive|string|in:days,months,hours|nullable',
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
            'equipment_id.required' => 'O equipamento é obrigatório.',
            'equipment_id.exists' => 'O equipamento selecionado é inválido.',
            'type.required' => 'O tipo de manutenção é obrigatório.',
            'type.in' => 'O tipo deve ser preventiva ou corretiva.',
            'priority.required' => 'A prioridade é obrigatória.',
            'priority.in' => 'A prioridade deve ser baixa, média, alta ou crítica.',
            'description.required' => 'A descrição é obrigatória.',
            'description.max' => 'A descrição não pode exceder 5000 caracteres.',
            'scheduled_date.date' => 'A data agendada deve ser uma data válida.',
            'scheduled_date.after_or_equal' => 'A data agendada deve ser hoje ou uma data futura.',
            'interval_value.required_if' => 'O valor do intervalo é obrigatório para manutenção preventiva.',
            'interval_value.integer' => 'O valor do intervalo deve ser um número inteiro.',
            'interval_value.min' => 'O valor do intervalo deve ser no mínimo 1.',
            'interval_unit.required_if' => 'A unidade do intervalo é obrigatória para manutenção preventiva.',
            'interval_unit.in' => 'A unidade do intervalo deve ser meses, dias ou horas.',
        ];
    }
}
