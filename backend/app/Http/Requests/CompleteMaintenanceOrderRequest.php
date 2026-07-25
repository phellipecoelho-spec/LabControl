<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteMaintenanceOrderRequest extends FormRequest
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
            'resolution' => 'nullable|string|max:5000',
            'time_spent' => 'nullable|numeric|min:0|max:99999.99',
            'cost' => 'nullable|numeric|min:0|max:999999999.99',
            'completed_at' => 'nullable|date',
            'parts' => 'nullable|array',
            'parts.*.inventory_item_id' => 'required_with:parts|string|exists:inventory_items,id',
            'parts.*.quantity' => 'required_with:parts|numeric|min:0.0001|max:999999.9999',
            'parts.*.unit_cost' => 'nullable|numeric|min:0|max:999999.99',
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
            'resolution.max' => 'O parecer técnico não pode exceder 5000 caracteres.',
            'time_spent.numeric' => 'O tempo gasto deve ser um valor numérico.',
            'time_spent.min' => 'O tempo gasto não pode ser negativo.',
            'time_spent.max' => 'O tempo gasto não pode exceder 99999.99 horas.',
            'cost.numeric' => 'O custo deve ser um valor numérico.',
            'cost.min' => 'O custo não pode ser negativo.',
            'cost.max' => 'O custo não pode exceder R$ 999.999.999,99.',
            'completed_at.date' => 'A data de conclusão deve ser uma data válida.',
            'parts.array' => 'As peças devem ser enviadas como uma lista.',
            'parts.*.inventory_item_id.required_with' => 'O item de inventário é obrigatório para cada peça.',
            'parts.*.inventory_item_id.exists' => 'O item de inventário informado é inválido.',
            'parts.*.quantity.required_with' => 'A quantidade é obrigatória para cada peça.',
            'parts.*.quantity.numeric' => 'A quantidade deve ser um valor numérico.',
            'parts.*.quantity.min' => 'A quantidade deve ser maior que zero.',
            'parts.*.quantity.max' => 'A quantidade excede o limite permitido.',
            'parts.*.unit_cost.numeric' => 'O custo unitário deve ser um valor numérico.',
            'parts.*.unit_cost.min' => 'O custo unitário não pode ser negativo.',
            'parts.*.unit_cost.max' => 'O custo unitário excede o limite permitido.',
        ];
    }
}
