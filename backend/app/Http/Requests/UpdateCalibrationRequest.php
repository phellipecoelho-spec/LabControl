<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalibrationRequest extends FormRequest
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
            'equipment_id' => 'sometimes|string|exists:equipments,id',
            'part_name' => 'nullable|string|max:255',
            'scheduled_date' => 'sometimes|date',
            'interval_value' => 'sometimes|integer|min:1',
            'interval_unit' => 'sometimes|string|in:months,days,hours',
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
            'equipment_id.exists' => 'O equipamento selecionado é inválido.',
            'part_name.max' => 'O nome da parte não pode exceder 255 caracteres.',
            'scheduled_date.date' => 'A data agendada deve ser uma data válida.',
            'interval_value.integer' => 'O valor do intervalo deve ser um número inteiro.',
            'interval_value.min' => 'O valor do intervalo deve ser no mínimo 1.',
            'interval_unit.in' => 'A unidade do intervalo deve ser meses, dias ou horas.',
            'responsible.max' => 'O responsável não pode exceder 255 caracteres.',
            'laboratory.max' => 'O laboratório não pode exceder 255 caracteres.',
            'notes.max' => 'As observações não podem exceder 2000 caracteres.',
        ];
    }
}
