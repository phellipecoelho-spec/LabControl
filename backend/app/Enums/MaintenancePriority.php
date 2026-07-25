<?php

namespace App\Enums;

/**
 * Prioridade de ordem de manutenção (D-03).
 */
enum MaintenancePriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    /**
     * Rótulo em português para exibição na UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baixa',
            self::Medium => 'Média',
            self::High => 'Alta',
            self::Critical => 'Crítica',
        };
    }
}
