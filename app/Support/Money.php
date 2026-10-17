<?php

namespace App\Support;

class Money
{
    /**
     * Format a numeric rupiah value as "Rp 12.500".
     */
    public static function format(int|float|string|null $value, bool $withSymbol = true): string
    {
        $value = round((float) ($value ?? 0));
        $formatted = number_format($value, 0, ',', '.');

        return $withSymbol ? 'Rp '.$formatted : $formatted;
    }

    /**
     * Round a value to the nearest step (0 = no rounding).
     * Matches the "banker's" style used across POS totals: round-half-up.
     */
    public static function roundTo(float $value, int $step): float
    {
        if ($step <= 0) {
            return round($value, 2);
        }

        return round($value / $step) * $step;
    }
}
