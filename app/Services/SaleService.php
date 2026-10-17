<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\PosException;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\User;
use App\Services\Sales\CheckoutData;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly PricingService $pricingService,
        private readonly DocumentNumberService $documentNumberService,
    ) {}

    /**
     * Recompute and persist a checkout entirely from server-trusted data
     * (Tech Spec §5.4). The client only ever supplies ids/qty/discount
     * intent — prices, totals, and stock effects are always derived here.
     */
    public function checkout(CheckoutData $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $user = User::findOrFail($data->userId);
            $customer = $data->customerId ? Customer::query()->lockForUpdate()->find($data->customerId) : null;

            // Lock product rows in ascending product_id order to avoid deadlocks
            // when multiple cashiers checkout overlapping products concurrently.
            $units = ProductUnit::query()
                ->with('product')
                ->whereIn('id', collect($data->items)->pluck('productUnitId'))
                ->get()
                ->keyBy('id');

            foreach ($units->sortBy('product_id') as $unit) {
                Product::query()->lockForUpdate()->find($unit->product_id);
            }

            $lineTotal = 0.0; // Σ harga × qty, before any discount — used for the cashier discount cap.
            $lineDiscountTotal = 0.0;
            $subtotal = 0.0;
            $costTotal = 0.0;
            $itemRows = [];

            foreach ($data->items as $item) {
                /** @var ProductUnit $unit */
                $unit = $units->get($item->productUnitId);
                if (! $unit) {
                    throw new PosException('Barang tidak ditemukan.');
                }
                $product = $unit->product;

                $price = $item->priceOverride !== null && $user->can('pos.override-price')
                    ? $item->priceOverride
                    : $this->pricingService->priceFor($unit, $item->qty, $customer);

                $lineGross = $price * $item->qty;
                $discountAmount = $this->applyDiscount($lineGross, $item->discountType, $item->discountValue);
                $itemSubtotal = round($lineGross - $discountAmount, 2);

                $qtyBase = $item->qty * (float) $unit->conversion_factor;
                $unitCostPerSoldUnit = round((float) $product->avg_cost * (float) $unit->conversion_factor, 2);

                $lineTotal += $lineGross;
                $lineDiscountTotal += $discountAmount;
                $subtotal += $itemSubtotal;
                $costTotal += $unitCostPerSoldUnit * $item->qty;

                $itemRows[] = [
                    'product' => $product,
                    'unit' => $unit,
                    'qty' => $item->qty,
                    'qty_base' => $qtyBase,
                    'price' => $price,
                    'discount_type' => $item->discountType,
                    'discount_value' => $item->discountValue,
                    'discount_amount' => $discountAmount,
                    'subtotal' => $itemSubtotal,
                    'unit_cost' => $unitCostPerSoldUnit,
                    'price_overridden' => $item->priceOverride !== null && $user->can('pos.override-price'),
                    'barcodes' => $item->barcodes ?: null,
                ];
            }

            $subtotal = round($subtotal, 2);
            $discountTotal = $this->applyDiscount($subtotal, $data->discountType, $data->discountValue);

            $totalDiscountPercent = $lineTotal > 0 ? (($lineDiscountTotal + $discountTotal) / $lineTotal) * 100 : 0;
            $maxCashierDiscount = (float) setting('pos.max_cashier_discount_percent', 10);
            if ($totalDiscountPercent > $maxCashierDiscount && ! $user->can('pos.discount-unlimited')) {
                throw new PosException(sprintf('Diskon melebihi batas %s%% untuk kasir. Perlu izin supervisor.', rtrim(rtrim(number_format($maxCashierDiscount, 1), '0'), '.')));
            }

            $taxEnabled = (bool) setting('tax.enabled', false);
            $taxInclusive = (bool) setting('tax.inclusive', true);
            $taxRate = (float) setting('tax.rate', 11);
            $taxableBase = $subtotal - $discountTotal;
            $taxAmount = $taxEnabled && ! $taxInclusive ? round($taxableBase * $taxRate / 100, 2) : 0.0;

            $beforeRounding = $taxableBase + $taxAmount;
            $rounding = (int) setting('pos.rounding', 0);
            $total = Money::roundTo($beforeRounding, $rounding);
            $roundingAmount = round($total - $beforeRounding, 2);

            [$paymentRows, $paidAmount, $changeAmount] = $this->resolvePayments($data->payments, $total);

            $paymentStatus = PaymentStatus::Paid;
            $dueDate = null;

            if ($paidAmount < $total) {
                if (! $customer) {
                    throw new PosException(sprintf('Pembayaran kurang %s', Money::format($total - $paidAmount, false)));
                }
                if (! $user->can('pos.credit-sale')) {
                    throw new PosException('Anda tidak memiliki izin untuk penjualan kredit.');
                }
                if ((float) $customer->credit_limit <= 0) {
                    throw new PosException('Pelanggan tidak memiliki fasilitas kredit.');
                }

                $outstanding = (float) Sale::query()
                    ->where('customer_id', $customer->id)
                    ->where('status', SaleStatus::Completed)
                    ->where('payment_status', '!=', PaymentStatus::Paid)
                    ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as outstanding')
                    ->value('outstanding');

                $shortfall = $total - $paidAmount;
                $available = (float) $customer->credit_limit - $outstanding;

                if ($shortfall > $available) {
                    throw new PosException(sprintf('Melebihi batas kredit (sisa %s)', Money::format(max($available, 0), false)));
                }

                $paymentStatus = $paidAmount > 0 ? PaymentStatus::Partial : PaymentStatus::Unpaid;
                $dueDate = now()->addDays(30)->toDateString();
            }

            $number = $this->documentNumberService->next('sale', $data->locationId);

            $sale = Sale::create([
                'number' => $number,
                'location_id' => $data->locationId,
                'user_id' => $data->userId,
                'shift_id' => $data->shiftId,
                'customer_id' => $data->customerId,
                'sold_at' => now(),
                'subtotal' => $subtotal,
                'discount_type' => $data->discountType,
                'discount_value' => $data->discountValue,
                'discount_amount' => $discountTotal,
                'tax_amount' => $taxAmount,
                'rounding_amount' => $roundingAmount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_status' => $paymentStatus,
                'due_date' => $dueDate,
                'status' => SaleStatus::Completed,
                'cost_total' => round($costTotal, 2),
                'notes' => $data->notes,
            ]);

            foreach ($itemRows as $row) {
                $sale->items()->create([
                    'product_id' => $row['product']->id,
                    'product_unit_id' => $row['unit']->id,
                    'product_name' => $row['product']->name,
                    'unit_name' => $row['unit']->unit_name,
                    'barcodes' => $row['barcodes'],
                    'conversion_factor' => $row['unit']->conversion_factor,
                    'qty' => $row['qty'],
                    'qty_base' => $row['qty_base'],
                    'price' => $row['price'],
                    'discount_type' => $row['discount_type'],
                    'discount_value' => $row['discount_value'],
                    'discount_amount' => $row['discount_amount'],
                    'subtotal' => $row['subtotal'],
                    'unit_cost' => $row['unit_cost'],
                    'price_overridden' => $row['price_overridden'],
                ]);

                $this->stockService->move(
                    product: $row['product'],
                    locationId: $data->locationId,
                    qtyChange: -$row['qty_base'],
                    type: StockMovementType::Sale,
                    reference: $sale,
                    unitCost: (float) $row['product']->avg_cost,
                    userId: $data->userId,
                );
            }

            foreach ($paymentRows as $payment) {
                $sale->payments()->create([
                    'direction' => 'in',
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'reference' => $payment['reference'] ?? null,
                    'paid_at' => now(),
                    'user_id' => $data->userId,
                    'shift_id' => $data->shiftId,
                    'location_id' => $data->locationId,
                    'is_initial' => true,
                ]);
            }

            return $sale->load('items', 'payments');
        });
    }

    /**
     * @param  list<Sales\CheckoutPaymentData>  $payments
     * @return array{0: list<array{method:string,amount:float,reference:?string}>, 1: float, 2: float}
     */
    private function resolvePayments(array $payments, float $total): array
    {
        $cashTotal = 0.0;
        $nonCashRows = [];

        foreach ($payments as $payment) {
            if ($payment->method === PaymentMethod::Cash->value) {
                $cashTotal += $payment->amount;
            } else {
                $nonCashRows[] = ['method' => $payment->method, 'amount' => $payment->amount, 'reference' => $payment->reference];
            }
        }

        $nonCashTotal = array_sum(array_column($nonCashRows, 'amount'));

        if ($nonCashTotal > $total + 0.01) {
            throw new PosException('Pembayaran non-tunai tidak boleh melebihi sisa tagihan.');
        }

        $remainingAfterNonCash = max(0, $total - $nonCashTotal);
        $appliedCash = min($cashTotal, $remainingAfterNonCash);
        $changeAmount = round($cashTotal - $appliedCash, 2);
        $paidAmount = round($nonCashTotal + $appliedCash, 2);

        $rows = $nonCashRows;
        if ($appliedCash > 0) {
            $rows[] = ['method' => PaymentMethod::Cash->value, 'amount' => round($appliedCash, 2), 'reference' => null];
        }

        return [$rows, $paidAmount, $changeAmount];
    }

    private function applyDiscount(float $base, ?string $type, ?float $value): float
    {
        if (! $type || ! $value) {
            return 0.0;
        }

        return $type === 'percent'
            ? round($base * $value / 100, 2)
            : min($value, $base);
    }

    /**
     * Void a completed sale (Tech Spec §5.5): reverse stock, void payments,
     * exclude from reports. Callers are responsible for the permission /
     * PIN check before calling this.
     */
    public function void(Sale $sale, string $reason, User $user): Sale
    {
        return DB::transaction(function () use ($sale, $reason, $user) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($sale->status === SaleStatus::Void) {
                throw new PosException('Transaksi ini sudah di-void.');
            }

            foreach ($sale->items as $item) {
                $this->stockService->move(
                    product: $item->product,
                    locationId: $sale->location_id,
                    qtyChange: $item->qty_base,
                    type: StockMovementType::SaleVoid,
                    reference: $sale,
                    unitCost: $item->product->avg_cost,
                    userId: $user->id,
                    note: "Void: {$reason}",
                );
            }

            Payment::query()
                ->where('payable_type', $sale->getMorphClass())
                ->where('payable_id', $sale->id)
                ->update(['voided_at' => now()]);

            $sale->update([
                'status' => SaleStatus::Void,
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ]);

            return $sale;
        });
    }
}
