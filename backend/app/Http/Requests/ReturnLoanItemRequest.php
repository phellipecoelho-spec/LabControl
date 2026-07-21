<?php

namespace App\Http\Requests;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class ReturnLoanItemRequest extends FormRequest
{
    private ?Loan $routeLoan = null;

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
            'returned_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $loan = $this->route('loan');
            $equipmentId = $this->input('equipment_id');

            if ($loan && $equipmentId) {
                $exists = $loan->items()
                    ->where('equipment_id', $equipmentId)
                    ->exists();

                if (!$exists) {
                    $validator->errors()->add(
                        'equipment_id',
                        'O equipamento informado não faz parte deste empréstimo.'
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'equipment_id.required' => 'O equipamento a ser devolvido é obrigatório.',
            'equipment_id.exists' => 'O equipamento selecionado é inválido.',
            'returned_at.date' => 'A data de devolução deve ser uma data válida.',
            'notes.max' => 'As observações não podem exceder 1000 caracteres.',
        ];
    }
}
