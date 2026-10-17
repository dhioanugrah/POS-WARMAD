<?php

namespace App\Support;

use App\Models\Product;

class UnitFormatter
{
    /**
     * Break a base-unit quantity down into the product's largest units first,
     * e.g. 85 Pcs with a "Dus" unit of 40 becomes "2 Dus 5 Pcs".
     */
    public static function breakdown(Product $product, float|string $qtyBase): string
    {
        $qtyBase = (float) $qtyBase;
        $negative = $qtyBase < 0;
        $remaining = abs($qtyBase);

        $units = $product->relationLoaded('units')
            ? $product->units
            : $product->units()->get();

        $units = $units->sortByDesc(fn ($unit) => (float) $unit->conversion_factor);

        $parts = [];

        foreach ($units as $unit) {
            $factor = (float) $unit->conversion_factor;

            if ($factor <= 0) {
                continue;
            }

            if ($factor == 1.0) {
                continue;
            }

            $count = intdiv((int) round($remaining * 1000), (int) round($factor * 1000));

            if ($count > 0) {
                $parts[] = $count.' '.$unit->unit_name;
                $remaining -= $count * $factor;
            }
        }

        $baseUnitName = $product->base_unit ?: 'Pcs';
        $remaining = round($remaining, 3);

        if ($remaining > 0 || empty($parts)) {
            $parts[] = self::formatQty($remaining).' '.$baseUnitName;
        }

        return ($negative ? '-' : '').implode(' ', $parts);
    }

    public static function formatQty(float $qty): string
    {
        if (floor($qty) == $qty) {
            return number_format($qty, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($qty, 3, ',', '.'), '0'), ',');
    }
}
