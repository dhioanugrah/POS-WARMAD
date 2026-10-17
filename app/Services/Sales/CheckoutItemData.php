<?php

namespace App\Services\Sales;

readonly class CheckoutItemData
{
    public function __construct(
        public int $productUnitId,
        public float $qty,
        public ?string $discountType = null,
        public ?float $discountValue = null,
        public ?float $priceOverride = null,
    ) {}
}
