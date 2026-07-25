<?php

namespace App\Enums;

/**
 * Resultado de parâmetro de aferição (D-04).
 *
 * Valores calculados automaticamente pelo VerificationService
 * com base nos limites de tolerância do template.
 */
enum VerificationResult: string
{
    case WithinRange = 'within_range';
    case OutsideRange = 'outside_range';
    case NotMeasured = 'not_measured';

    /**
     * Rótulo em português para exibição na UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::WithinRange => 'Dentro do Intervalo',
            self::OutsideRange => 'Fora do Intervalo',
            self::NotMeasured => 'Não Medido',
        };
    }

    /**
     * Cor semântica para indicadores visuais.
     */
    public function color(): string
    {
        return match ($this) {
            self::WithinRange => 'success',
            self::OutsideRange => 'danger',
            self::NotMeasured => 'warn',
        };
    }

    /**
     * Verifica se o resultado está dentro do intervalo esperado.
     */
    public function isWithinRange(): bool
    {
        return $this === self::WithinRange;
    }
}
