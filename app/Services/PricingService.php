<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ProductUnit;

class PricingService
{
    /**
     * Resolve the unit price for a given quantity (Tech Spec §5.4). Fase 1
     * only has the flat `price_sell`; tiered wholesale pricing (MST-05,
     * Fase 2) plugs in here later without changing callers.
     */
    public function priceFor(ProductUnit $unit, float $qty, ?Customer $customer = null): float
    {
        $tier = $unit->relationLoaded('priceTiers')
            ? $unit->priceTiers->filter(fn ($t) => (float) $t->min_qty <= $qty)->sortByDesc('min_qty')->first()
            : $unit->priceTiers()->where('min_qty', '<=', $qty)->orderByDesc('min_qty')->first();

        return (float) ($tier->price ?? $unit->price_sell);
    }
}
