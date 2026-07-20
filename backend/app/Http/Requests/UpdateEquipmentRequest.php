<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'serial_number' => 'sometimes|string|max:100',
            'category_id' => 'sometimes|exists:categories,id',
            'manufacturer_id' => 'sometimes|exists:manufacturers,id',
            'location' => 'sometimes|string|max:255',
            'patrimony_id' => 'nullable|string|max:50',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'acquisition_date' => 'nullable|date',
            'warranty_end' => 'nullable|date|after_or_equal:acquisition_date',
            'status' => 'nullable|string|in:active,inactive,maintenance,retired',
            'description' => 'nullable|string',
            'technical_specs' => 'nullable|string',
            'notes' => 'nullable|string',
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
            'name.max' => 'O nome do equipamento não pode exceder 255 caracteres.',
            'serial_number.max' => 'O número de série não pode exceder 100 caracteres.',
            'category_id.exists' => 'A categoria selecionada é inválida.',
            'manufacturer_id.exists' => 'O fabricante selecionado é inválido.',
            'location.max' => 'A localização não pode exceder 255 caracteres.',
            'supplier_id.exists' => 'O fornecedor selecionado é inválido.',
            'acquisition_date.date' => 'A data de aquisição deve ser uma data válida.',
            'warranty_end.date' => 'A data de fim da garantia deve ser uma data válida.',
            'warranty_end.after_or_equal' => 'A data de fim da garantia deve ser igual ou posterior à data de aquisição.',
            'status.in' => 'O status deve ser: active, inactive, maintenance ou retired.',
        ];
    }
}