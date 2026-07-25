<?php

namespace App\Enums;

/**
 * Status de ordem de manutenção com transições de estado (D-02).
 *
 * Regras de transição:
 * - open       → in_progress, cancelled
 * - in_progress → completed, cancelled
 * - completed  → (terminal)
 * - cancelled  → (terminal)
 */
enum MaintenanceStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Rótulo em português para exibição na UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::InProgress => 'Em Andamento',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    /**
     * Verifica se a transição para o status alvo é permitida (D-02).
     *
     * @param  MaintenanceStatus  $target  Status desejado
     * @return bool
     */
    public function canTransitionTo(MaintenanceStatus $target): bool
    {
        return match ($this) {
            self::Open => in_array($target, [self::InProgress, self::Cancelled], true),
            self::InProgress => in_array($target, [self::Completed, self::Cancelled], true),
            self::Completed => false,
            self::Cancelled => false,
        };
    }
}
