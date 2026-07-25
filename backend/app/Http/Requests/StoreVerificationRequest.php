<?php

namespace App\Http\Requests;

use App\Models\Equipment;
use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationRequest extends FormRequest
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
            'verified_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'params' => 'required|array|min:1',
            'params.*' => [
                'required',
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
            'equipment_id.required' => 'O equipamento é obrigatório.',
            'equipment_id.exists' => 'O equipamento selecionado é inválido.',
            'params.required' => 'Os parâmetros são obrigatórios.',
            'params.min' => 'Pelo menos um parâmetro deve ser informado.',
            'params.*.required' => 'Cada valor de parâmetro é obrigatório.',
            'params.*.numeric' => 'Cada valor de parâmetro deve ser numérico.',
            'params.*.regex' => 'Cada valor de parâmetro deve ser um número válido.',
            'verified_at.date' => 'A data de verificação deve ser uma data válida.',
            'notes.max' => 'As observações não podem exceder 2000 caracteres.',
        ];
    }

    /**
     * Configure the validator instance — ensure equipment has verification templates.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $equipmentId = $this->input('equipment_id');
            if (! $equipmentId) {
                return;
            }

            $equipment = Equipment::with('category.verificationTemplates')->find($equipmentId);
            if (! $equipment) {
                return;
            }

            if ($equipment->category && $equipment->category->verificationTemplates->isEmpty()) {
                $validator->errors()->add(
                    'equipment_id',
                    'A categoria do equipamento não possui templates de aferição configurados.'
                );
            }
        });
    }
}
