<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalibrationRequest extends FormRequest
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
            'part_name' => 'nullable|string|max:255',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'interval_value' => 'required|integer|min:1',
            'interval_unit' => 'required|string|in:months,days,hours',
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
            'equipment_id.required' => 'O equipamento é obrigatório.',
            'equipment_id.exists' => 'O equipamento selecionado é inválido.',
            'part_name.max' => 'O nome da parte não pode exceder 255 caracteres.',
            'scheduled_date.required' => 'A data agendada é obrigatória.',
            'scheduled_date.date' => 'A data agendada deve ser uma data válida.',
            'scheduled_date.after_or_equal' => 'A data agendada deve ser hoje ou uma data futura.',
            'interval_value.required' => 'O valor do intervalo é obrigatório.',
            'interval_value.integer' => 'O valor do intervalo deve ser um número inteiro.',
            'interval_value.min' => 'O valor do intervalo deve ser no mínimo 1.',
            'interval_unit.required' => 'A unidade do intervalo é obrigatória.',
            'interval_unit.in' => 'A unidade do intervalo deve ser meses, dias ou horas.',
            'responsible.max' => 'O responsável não pode exceder 255 caracteres.',
            'laboratory.max' => 'O laboratório não pode exceder 255 caracteres.',
            'notes.max' => 'As observações não podem exceder 2000 caracteres.',
        ];
    }
}
