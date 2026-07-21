<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100|unique:inventory_items,code',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:inventory_categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'unit' => 'required|string|in:UN,KG,L,CX,M,M2,M3,PC,PCT,CJ',
            'min_stock' => 'required|integer|min:0',
            'batch_lot' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'physical_location' => 'nullable|string|max:255',
            'initial_quantity' => 'integer|min:0',
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
            'name.required' => 'O nome do item é obrigatório.',
            'name.max' => 'O nome do item não pode exceder 255 caracteres.',
            'code.max' => 'O código do item não pode exceder 100 caracteres.',
            'code.unique' => 'Este código já está em uso por outro item.',
            'category_id.required' => 'A categoria é obrigatória.',
            'category_id.exists' => 'A categoria selecionada é inválida.',
            'supplier_id.required' => 'O fornecedor é obrigatório.',
            'supplier_id.exists' => 'O fornecedor selecionado é inválido.',
            'unit.required' => 'A unidade de medida é obrigatória.',
            'unit.in' => 'A unidade de medida deve ser: UN, KG, L, CX, M, M², M³, PC, PCT ou CJ.',
            'min_stock.required' => 'O estoque mínimo é obrigatório.',
            'min_stock.integer' => 'O estoque mínimo deve ser um número inteiro.',
            'min_stock.min' => 'O estoque mínimo não pode ser negativo.',
            'batch_lot.max' => 'O lote não pode exceder 100 caracteres.',
            'expiry_date.date' => 'A data de validade deve ser uma data válida.',
            'physical_location.max' => 'A localização física não pode exceder 255 caracteres.',
            'initial_quantity.integer' => 'A quantidade inicial deve ser um número inteiro.',
            'initial_quantity.min' => 'A quantidade inicial não pode ser negativa.',
        ];
    }
}
