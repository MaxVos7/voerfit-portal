<?php

namespace App\Models\Farm\Concerns;

trait CalculatesMeetmelk
{
    /**
     * Meetmelk: fat and protein corrected milk (FPCM) in kg, rounded to 2 decimals.
     *
     * This is not the measured kg of the sample (Nijhof, 2026-09-09).
     */
    protected static function meetmelk(?float $milkKg, ?float $fatPercentage, ?float $proteinPercentage): ?float
    {
        if ($milkKg === null || $fatPercentage === null || $proteinPercentage === null) {
            return null;
        }

        return round((0.337 + 0.116 * $fatPercentage + 0.06 * $proteinPercentage) * $milkKg, 2);
    }
}
