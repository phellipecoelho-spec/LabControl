<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
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
            'borrower_id' => 'required|string|exists:users,id',
            'equipment_ids' => 'required|array|min:1',
            'equipment_ids.*' => 'string|exists:equipments,id',
            'borrowed_at' => 'required|date|after_or_equal:today',
            'expected_return_at' => 'required|date|after:borrowed_at',
            'reason' => 'nullable|string|max:1000',
            'destination' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'approved_by' => 'nullable|string|exists:users,id',
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
            'borrower_id.required' => 'O tomador do empréstimo é obrigatório.',
            'borrower_id.exists' => 'O tomador selecionado é inválido.',
            'equipment_ids.required' => 'Selecione pelo menos um equipamento.',
            'equipment_ids.min' => 'Selecione pelo menos um equipamento.',
            'equipment_ids.*.exists' => 'Um ou mais equipamentos selecionados são inválidos.',
            'borrowed_at.required' => 'A data de retirada é obrigatória.',
            'borrowed_at.date' => 'A data de retirada deve ser uma data válida.',
            'borrowed_at.after_or_equal' => 'A data de retirada deve ser hoje ou uma data futura.',
            'expected_return_at.required' => 'A data prevista de devolução é obrigatória.',
            'expected_return_at.date' => 'A data prevista de devolução deve ser uma data válida.',
            'expected_return_at.after' => 'A data prevista de devolução deve ser posterior à data de retirada.',
            'reason.max' => 'O motivo não pode exceder 1000 caracteres.',
            'destination.max' => 'O destino não pode exceder 255 caracteres.',
            'contact.max' => 'O contato não pode exceder 255 caracteres.',
            'notes.max' => 'As observações não podem exceder 2000 caracteres.',
            'approved_by.exists' => 'O aprovador selecionado é inválido.',
        ];
    }
}
