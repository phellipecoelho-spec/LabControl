<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('inventory_item'),
        ]);
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
            'code' => 'nullable|string|max:100|unique:inventory_items,code,' . $this->id,
            'description' => 'nullable|string',
            'category_id' => 'sometimes|exists:inventory_categories,id',
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'unit' => 'sometimes|string|in:UN,KG,L,CX,M,M2,M3,PC,PCT,CJ',
            'min_stock' => 'sometimes|integer|min:0',
            'batch_lot' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'physical_location' => 'nullable|string|max:255',
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
            'name.max' => 'O nome do item não pode exceder 255 caracteres.',
            'code.max' => 'O código do item não pode exceder 100 caracteres.',
            'code.unique' => 'Este código já está em uso por outro item.',
            'category_id.exists' => 'A categoria selecionada é inválida.',
            'supplier_id.exists' => 'O fornecedor selecionado é inválido.',
            'unit.in' => 'A unidade de medida deve ser: UN, KG, L, CX, M, M², M³, PC, PCT ou CJ.',
            'min_stock.integer' => 'O estoque mínimo deve ser um número inteiro.',
            'min_stock.min' => 'O estoque mínimo não pode ser negativo.',
            'batch_lot.max' => 'O lote não pode exceder 100 caracteres.',
            'expiry_date.date' => 'A data de validade deve ser uma data válida.',
            'physical_location.max' => 'A localização física não pode exceder 255 caracteres.',
        ];
    }
}
