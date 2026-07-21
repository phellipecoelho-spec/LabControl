<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanRequest extends FormRequest
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
            'borrower_id' => 'sometimes|string|exists:users,id',
            'borrowed_at' => 'sometimes|date',
            'expected_return_at' => 'sometimes|date|after:borrowed_at',
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
            'borrower_id.exists' => 'O tomador selecionado é inválido.',
            'borrowed_at.date' => 'A data de retirada deve ser uma data válida.',
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
