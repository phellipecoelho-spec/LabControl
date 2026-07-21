<?php

namespace App\Enums;

/**
 * Status de empréstimo com transições de estado (D-03).
 *
 * Regras de transição:
 * - reserved  → active, cancelled
 * - active    → returned
 * - returned  → (terminal)
 * - cancelled → (terminal)
 */
enum LoanStatus: string
{
    case Reserved = 'reserved';
    case Active = 'active';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    /**
     * Rótulo em português para exibição na UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Reserved => 'Reservado',
            self::Active => 'Ativo',
            self::Returned => 'Devolvido',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Verifica se a transição para o status alvo é permitida (D-03).
     *
     * @param  LoanStatus  $target  Status desejado
     * @return bool
     */
    public function canTransitionTo(LoanStatus $target): bool
    {
        return match ($this) {
            self::Reserved => in_array($target, [self::Active, self::Cancelled], true),
            self::Active => $target === self::Returned,
            self::Returned => false,
            self::Cancelled => false,
        };
    }
}
