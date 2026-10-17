<?php

namespace App\Services\Sales;

readonly class CheckoutPaymentData
{
    public function __construct(
        public string $method,
        public float $amount,
        public ?string $reference = null,
    ) {}
}
