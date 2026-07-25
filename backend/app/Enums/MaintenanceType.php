<?php

namespace App\Enums;

/**
 * Tipo de manutenção (D-01).
 *
 * - preventive: manutenção preventiva (interval-based)
 * - corrective: manutenção corretiva (on-demand)
 */
enum MaintenanceType: string
{
    case Preventive = 'preventive';
    case Corrective = 'corrective';

    /**
     * Rótulo em português para exibição na UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Preventive => 'Preventiva',
            self::Corrective => 'Corretiva',
        };
    }
}
