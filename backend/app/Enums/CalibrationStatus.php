<?php

namespace App\Enums;

/**
 * Status de calibração com transições de estado (D-03).
 *
 * Regras de transição:
 * - scheduled  → completed, cancelled
 * - completed  → (terminal)
 * - cancelled  → (terminal)
 */
enum CalibrationStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Rótulo em português para exibição na UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendada',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    /**
     * Verifica se a transição para o status alvo é permitida (D-03).
     *
     * @param  CalibrationStatus  $target  Status desejado
     * @return bool
     */
    public function canTransitionTo(CalibrationStatus $target): bool
    {
        return match ($this) {
            self::Scheduled => in_array($target, [self::Completed, self::Cancelled], true),
            self::Completed => false,
            self::Cancelled => false,
        };
    }
}
