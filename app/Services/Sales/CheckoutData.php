<?php

namespace App\Services\Sales;

readonly class CheckoutData
{
    /**
     * @param  list<CheckoutItemData>  $items
     * @param  list<CheckoutPaymentData>  $payments
     */
    public function __construct(
        public int $locationId,
        public int $userId,
        public ?int $shiftId,
        public ?int $customerId,
        public array $items,
        public array $payments,
        public ?string $discountType = null,
        public ?float $discountValue = null,
        public ?string $notes = null,
    ) {}
}
