<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryMovementRequest extends FormRequest
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
            'item_id' => 'required|exists:inventory_items,id',
            'type' => 'required|string|in:purchase,consumption,adjustment,disposal,return',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|required_if:type,adjustment|required_if:type,disposal',
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
            'item_id.required' => 'O item é obrigatório.',
            'item_id.exists' => 'O item selecionado é inválido.',
            'type.required' => 'O tipo de movimentação é obrigatório.',
            'type.in' => 'O tipo de movimentação deve ser: purchase, consumption, adjustment, disposal ou return.',
            'quantity.required' => 'A quantidade é obrigatória.',
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity.min' => 'A quantidade deve ser no mínimo 1.',
            'reason.required_if' => 'O motivo é obrigatório para movimentações do tipo :type.',
            'reason.string' => 'O motivo deve ser um texto válido.',
        ];
    }
}
