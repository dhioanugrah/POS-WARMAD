<?php

namespace App\Livewire\Pos;

use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Exceptions\PosException;
use App\Livewire\Concerns\PicksBarcodes;
use App\Models\CashCategory;
use App\Models\CashTransaction;
use App\Models\HeldCart;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Services\DocumentNumberService;
use App\Services\Sales\CheckoutData;
use App\Services\Sales\CheckoutItemData;
use App\Services\Sales\CheckoutPaymentData;
use App\Services\SaleService;
use App\Services\ShiftService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.pos')]
class Cashier extends Component
{
    use PicksBarcodes;

    public ?int $shiftId = null;

    public bool $showOpenShiftModal = false;

    public string $operatorName = '';

    public $openingCash = 0;

    public string $search = '';

    public int $highlightedIndex = 0;

    /** @var array<int, array<string, mixed>> keyed by product_unit_id */
    public array $cart = [];

    public ?int $customerId = null;

    public ?string $totalDiscountType = null;

    public $totalDiscountValue = null;

    public bool $showDiscountModal = false;

    public bool $showPaymentModal = false;

    /** @var array<int, array{method: string, amount: mixed}> */
    public array $paymentRows = [];

    public string $paymentMethod = 'cash';

    public $paymentAmount = 0;

    public bool $showHoldModal = false;

    public string $holdLabel = '';

    public bool $showHeldListModal = false;

    public bool $showHistoryModal = false;

    public bool $showCloseShiftModal = false;

    public $countedCash = 0;

    public bool $showCashDrawerModal = false;

    public string $cashDrawerType = 'out';

    public ?int $cashDrawerCategoryId = null;

    public $cashDrawerAmount = 0;

    public string $cashDrawerDescription = '';

    public ?int $lastSaleId = null;

    public function mount(): void
    {
        $this->loadShift();
    }

    private function loadShift(): void
    {
        $shift = Shift::query()
            ->where('user_id', auth()->id())
            ->where('status', ShiftStatus::Open)
            ->first();

        $this->shiftId = $shift?->id;
        $this->showOpenShiftModal = ! $shift;
    }

    public function getShiftProperty(): ?Shift
    {
        return $this->shiftId ? Shift::find($this->shiftId) : null;
    }

    private function locationId(): int
    {
        return $this->shift?->location_id ?? auth()->user()->default_location_id ?? Location::query()->where('is_active', true)->value('id');
    }

    public function openShift(): void
    {
        $this->validate([
            'operatorName' => 'required|string|max:100',
            'openingCash' => 'required|numeric|min:0',
        ], [], ['operatorName' => 'nama penjaga', 'openingCash' => 'modal awal']);

        $locationId = auth()->user()->default_location_id ?? Location::query()->where('is_active', true)->value('id');

        try {
            $shift = app(ShiftService::class)->open(auth()->user(), $locationId, (float) $this->openingCash, trim($this->operatorName));
            $this->shiftId = $shift->id;
            $this->showOpenShiftModal = false;
        } catch (PosException $e) {
            $this->addError('openingCash', $e->getMessage());
        }
    }

    // ---------- Search / scan (POS-02, 03, 04, 06) ----------

    public function updatedSearch(): void
    {
        $this->highlightedIndex = 0;
        $this->tryDirectMatch($this->search);
    }

    /** Returns true and clears search if an exact barcode/code/qty*code match was consumed. */
    private function tryDirectMatch(string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }

        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*\*\s*(.+)$/', $trimmed, $m)) {
            $qty = (float) str_replace(',', '.', $m[1]);
            $product = Product::query()->where('code', $m[2])->where('is_active', true)->first();
            if ($product) {
                $unit = $product->units()->where('is_default_sale', true)->first() ?? $product->units()->first();
                if ($unit) {
                    $this->addToCart($unit->id, $qty);
                    $this->search = '';

                    return true;
                }
            }
        }

        $unit = ProductUnit::query()->whereAnyBarcode($trimmed)->whereHas('product', fn ($q) => $q->where('is_active', true))->first();
        if ($unit) {
            $this->addToCart($unit->id);
            if (isset($this->cart[$unit->id])) {
                $this->cart[$unit->id]['barcodes'][] = $trimmed;
            }
            $this->search = '';

            return true;
        }

        $product = Product::query()->where('code', $trimmed)->where('is_active', true)->first();
        if ($product) {
            $unit = $product->units()->where('is_default_sale', true)->first() ?? $product->units()->first();
            if ($unit) {
                $this->addToCart($unit->id);
                $this->search = '';

                return true;
            }
        }

        return false;
    }

    /**
     * Pick a barcode from the list instead of scanning it.
     */
    public function pickBarcode(string $barcode): void
    {
        $this->tryDirectMatch($barcode);
    }

    public function handleEnter(): void
    {
        if ($this->tryDirectMatch($this->search)) {
            return;
        }

        $results = $this->searchResults();
        if ($results->isEmpty()) {
            return;
        }

        $index = min($this->highlightedIndex, $results->count() - 1);
        $this->addToCart($results->values()[$index]->id);
        $this->search = '';
    }

    public function arrowDown(): void
    {
        $count = $this->searchResults()->count();
        if ($count > 0) {
            $this->highlightedIndex = min($this->highlightedIndex + 1, $count - 1);
        }
    }

    public function arrowUp(): void
    {
        $this->highlightedIndex = max($this->highlightedIndex - 1, 0);
    }

    private function searchResults()
    {
        $value = trim($this->search);
        if ($value === '') {
            return collect();
        }

        return ProductUnit::query()
            ->where('is_default_sale', true)
            ->whereHas('product', fn ($q) => $q->where('is_active', true)
                ->where(fn ($q2) => $q2->where('name', 'like', "%{$value}%")->orWhere('code', 'like', "%{$value}%")))
            ->with('product')
            ->limit(20)
            ->get()
            ->sortBy(fn ($u) => $u->product->name)
            ->values();
    }

    // ---------- Cart ----------

    public function addToCart(int $productUnitId, float $qty = 1): void
    {
        $unit = ProductUnit::with('product.units')->find($productUnitId);
        if (! $unit || ! $unit->product->is_active) {
            $this->dispatch('notify', message: 'Barang nonaktif atau tidak ditemukan.', type: 'error');

            return;
        }

        $product = $unit->product;

        if (isset($this->cart[$unit->id])) {
            $this->cart[$unit->id]['qty'] += $qty;
        } else {
            $stockQty = $product->type->value === 'stock'
                ? (float) $product->stocks()->where('location_id', $this->locationId())->value('qty')
                : 0;

            $this->cart[$unit->id] = [
                'product_id' => $product->id,
                'product_unit_id' => $unit->id,
                'name' => $product->name,
                'unit_name' => $unit->unit_name,
                'conversion_factor' => (float) $unit->conversion_factor,
                'available_units' => $product->units->map(fn ($u) => [
                    'id' => $u->id, 'unit_name' => $u->unit_name, 'conversion_factor' => (float) $u->conversion_factor, 'price_sell' => (float) $u->price_sell,
                ])->all(),
                'qty' => $qty,
                'price' => (float) $unit->price_sell,
                'discount_type' => null,
                'discount_value' => null,
                'stock_qty' => $stockQty,
                'min_stock' => (float) $product->min_stock,
                'is_service' => $product->type->value === 'service',
                'barcodes' => [],
            ];
        }

        $this->dispatch('focus-search');
    }

    public function updateQty(int $productUnitId, float $qty): void
    {
        if ($qty <= 0) {
            unset($this->cart[$productUnitId]);

            return;
        }
        $this->cart[$productUnitId]['qty'] = $qty;
    }

    public function changeUnit(int $productUnitId, int $newUnitId): void
    {
        $row = $this->cart[$productUnitId];
        $newUnit = collect($row['available_units'])->firstWhere('id', $newUnitId);
        if (! $newUnit) {
            return;
        }

        unset($this->cart[$productUnitId]);
        $row['product_unit_id'] = $newUnit['id'];
        $row['unit_name'] = $newUnit['unit_name'];
        $row['conversion_factor'] = $newUnit['conversion_factor'];
        $row['price'] = $newUnit['price_sell'];
        $this->cart[$newUnit['id']] = $row;
    }

    public function setItemDiscount(int $productUnitId, ?string $type, $value): void
    {
        $this->cart[$productUnitId]['discount_type'] = $type ?: null;
        $this->cart[$productUnitId]['discount_value'] = $value !== '' ? (float) $value : null;
    }

    public function removeRow(int $productUnitId): void
    {
        unset($this->cart[$productUnitId]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->customerId = null;
        $this->totalDiscountType = null;
        $this->totalDiscountValue = null;
        $this->dispatch('cart-cleared');
    }

    public function rowSubtotal(array $row): float
    {
        $gross = $row['price'] * $row['qty'];
        $discount = $this->discountAmount($gross, $row['discount_type'], $row['discount_value']);

        return round($gross - $discount, 2);
    }

    private function discountAmount(float $base, ?string $type, $value): float
    {
        if (! $type || ! $value) {
            return 0.0;
        }

        return $type === 'percent' ? round($base * (float) $value / 100, 2) : min((float) $value, $base);
    }

    public function getSubtotalProperty(): float
    {
        return round(collect($this->cart)->sum(fn ($row) => $this->rowSubtotal($row)), 2);
    }

    public function getTotalDiscountAmountProperty(): float
    {
        return $this->discountAmount($this->subtotal, $this->totalDiscountType, $this->totalDiscountValue);
    }

    public function getEstimatedTotalProperty(): float
    {
        $beforeRounding = $this->subtotal - $this->totalDiscountAmount;
        $rounding = (int) setting('pos.rounding', 0);

        return Money::roundTo($beforeRounding, $rounding);
    }

    #[On('customer-selected')]
    public function setCustomer(?int $customerId): void
    {
        $this->customerId = $customerId;
    }

    // ---------- Payment ----------

    public function openPayment(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'Keranjang masih kosong.', type: 'error');

            return;
        }

        $this->paymentRows = [];
        $this->paymentAmount = $this->estimatedTotal;
        $this->paymentMethod = 'cash';
        $this->showPaymentModal = true;
    }

    public function quickCash(float $amount): void
    {
        $this->paymentAmount = $amount;
    }

    public function addPaymentRow(): void
    {
        if ((float) $this->paymentAmount <= 0) {
            return;
        }

        $this->paymentRows[] = ['method' => $this->paymentMethod, 'amount' => (float) $this->paymentAmount];

        $paid = collect($this->paymentRows)->sum('amount');
        $remaining = max(0, $this->estimatedTotal - $paid);
        $this->paymentAmount = $remaining;
    }

    public function removePaymentRow(int $index): void
    {
        unset($this->paymentRows[$index]);
        $this->paymentRows = array_values($this->paymentRows);
    }

    /**
     * Committed split rows plus whatever is currently typed in the amount
     * field but not yet added via "+ Tambah Pembayaran" — the pending value
     * always counts as an implicit final payment.
     *
     * @return list<array{method: string, amount: float}>
     */
    private function allPaymentRows(): array
    {
        $rows = $this->paymentRows;
        if ((float) $this->paymentAmount > 0) {
            $rows[] = ['method' => $this->paymentMethod, 'amount' => (float) $this->paymentAmount];
        }

        return $rows;
    }

    public function getPaymentTotalProperty(): float
    {
        return collect($this->allPaymentRows())->sum('amount');
    }

    public function pay(): void
    {
        $payments = collect($this->allPaymentRows())->map(fn ($r) => new CheckoutPaymentData($r['method'], (float) $r['amount']))->all();

        $items = collect($this->cart)->map(fn ($row) => new CheckoutItemData(
            productUnitId: $row['product_unit_id'],
            qty: (float) $row['qty'],
            discountType: $row['discount_type'],
            discountValue: $row['discount_value'] !== null ? (float) $row['discount_value'] : null,
            barcodes: array_values(array_unique($row['barcodes'] ?? [])),
        ))->values()->all();

        try {
            $sale = app(SaleService::class)->checkout(new CheckoutData(
                locationId: $this->locationId(),
                userId: auth()->id(),
                shiftId: $this->shiftId,
                customerId: $this->customerId,
                items: $items,
                payments: $payments,
                discountType: $this->totalDiscountType,
                discountValue: $this->totalDiscountValue !== null ? (float) $this->totalDiscountValue : null,
            ));
        } catch (PosException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');

            return;
        }

        $this->lastSaleId = $sale->id;
        $this->showPaymentModal = false;
        $change = (float) $sale->change_amount;
        $this->clearCart();
        $this->dispatch('notify', message: $change > 0 ? 'Transaksi berhasil. Kembalian '.Money::format($change) : 'Transaksi berhasil.', type: 'success');
        if (setting('receipt.auto_print', true)) {
            $this->dispatch('print-receipt', saleId: $sale->id);
        }
        $this->dispatch('focus-search');
    }

    public function reprintLast(): void
    {
        if ($this->lastSaleId) {
            $this->dispatch('print-receipt', saleId: $this->lastSaleId, copy: true);
        } else {
            $this->dispatch('notify', message: 'Belum ada transaksi untuk dicetak ulang.', type: 'error');
        }
    }

    // ---------- Hold cart ----------

    public function openHold(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'Keranjang masih kosong.', type: 'error');

            return;
        }
        $this->holdLabel = 'Antrian #'.(HeldCart::query()->where('user_id', auth()->id())->count() + 1);
        $this->showHoldModal = true;
    }

    public function holdCart(): void
    {
        $this->validate(['holdLabel' => 'required|string|max:100']);

        HeldCart::create([
            'user_id' => auth()->id(),
            'location_id' => $this->locationId(),
            'label' => $this->holdLabel,
            'customer_id' => $this->customerId,
            'payload' => [
                'items' => collect($this->cart)->map(fn ($row) => [
                    'product_unit_id' => $row['product_unit_id'],
                    'qty' => $row['qty'],
                    'discount_type' => $row['discount_type'],
                    'discount_value' => $row['discount_value'],
                ])->values()->all(),
                'discount_type' => $this->totalDiscountType,
                'discount_value' => $this->totalDiscountValue,
            ],
        ]);

        $this->cart = [];
        $this->customerId = null;
        $this->totalDiscountType = null;
        $this->totalDiscountValue = null;
        $this->showHoldModal = false;
        $this->dispatch('notify', message: 'Keranjang ditahan.', type: 'success');
    }

    public function resumeHeldCart(int $id): void
    {
        $held = HeldCart::query()->where('user_id', auth()->id())->findOrFail($id);

        $this->cart = [];
        foreach ($held->payload['items'] ?? [] as $item) {
            $this->addToCart($item['product_unit_id'], (float) $item['qty']);
            if (isset($this->cart[$item['product_unit_id']])) {
                $this->cart[$item['product_unit_id']]['discount_type'] = $item['discount_type'] ?? null;
                $this->cart[$item['product_unit_id']]['discount_value'] = $item['discount_value'] ?? null;
            }
        }
        $this->customerId = $held->customer_id;
        $this->totalDiscountType = $held->payload['discount_type'] ?? null;
        $this->totalDiscountValue = $held->payload['discount_value'] ?? null;

        $held->delete();
        $this->showHeldListModal = false;
    }

    public function deleteHeldCart(int $id): void
    {
        HeldCart::query()->where('user_id', auth()->id())->where('id', $id)->delete();
    }

    // ---------- Cash drawer ----------

    public function openCashDrawer(string $type): void
    {
        $this->cashDrawerType = $type;
        $this->cashDrawerAmount = 0;
        $this->cashDrawerDescription = '';
        $this->cashDrawerCategoryId = null;
        $this->showCashDrawerModal = true;
    }

    public function submitCashDrawer(): void
    {
        $this->validate([
            'cashDrawerAmount' => 'required|numeric|min:1',
            'cashDrawerCategoryId' => 'required|exists:cash_categories,id',
            'cashDrawerDescription' => 'required|string|max:255',
        ]);

        DB::transaction(function () {
            $number = app(DocumentNumberService::class)->next($this->cashDrawerType === 'in' ? 'cash_in' : 'cash_out', $this->locationId());

            CashTransaction::create([
                'number' => $number,
                'location_id' => $this->locationId(),
                'user_id' => auth()->id(),
                'shift_id' => $this->shiftId,
                'cash_category_id' => $this->cashDrawerCategoryId,
                'type' => $this->cashDrawerType,
                'method' => 'cash',
                'amount' => $this->cashDrawerAmount,
                'description' => $this->cashDrawerDescription,
                'occurred_at' => now(),
            ]);
        });

        $this->showCashDrawerModal = false;
        $this->dispatch('notify', message: 'Kas laci tercatat.', type: 'success');
    }

    // ---------- Shift close ----------

    public function openCloseShift(): void
    {
        $this->countedCash = 0;
        $this->showCloseShiftModal = true;
    }

    public function closeShift(): void
    {
        $this->validate(['countedCash' => 'required|numeric|min:0']);

        app(ShiftService::class)->close($this->shift, (float) $this->countedCash);

        $this->showCloseShiftModal = false;
        $this->loadShift();
        $this->dispatch('notify', message: 'Shift ditutup.', type: 'success');
    }

    public function voidSale(int $saleId, string $reason): void
    {
        if (! auth()->user()->can('sales.void')) {
            $this->dispatch('notify', message: 'Anda tidak memiliki izin untuk void.', type: 'error');

            return;
        }

        $sale = Sale::findOrFail($saleId);
        app(SaleService::class)->void($sale, $reason, auth()->user());
        $this->dispatch('notify', message: 'Transaksi dibatalkan (void).', type: 'success');
    }

    /**
     * Item cards for picking without a scanner: each card holds the item's
     * barcodes that are neither in the cart nor already sold.
     */
    private function barcodeProducts(): Collection
    {
        $soldBarcodes = SaleItem::query()
            ->whereNotNull('barcodes')
            ->whereHas('sale', fn ($q) => $q->where('status', SaleStatus::Completed))
            ->pluck('barcodes')
            ->flatten()
            ->all();

        $options = $this->barcodeOptions(
            $this->shift?->location_id ?? auth()->user()->default_location_id ?? Location::query()->where('is_active', true)->value('id'),
            array_keys($this->cart),
            excludeBarcodes: [...collect($this->cart)->pluck('barcodes')->flatten()->all(), ...$soldBarcodes],
        );

        return $options->groupBy('product_id')
            ->map(fn (Collection $barcodes) => [
                'product_id' => $barcodes->first()['product_id'],
                'name' => $barcodes->first()['product_name'],
                'code' => $barcodes->first()['product_code'],
                'image_path' => $barcodes->first()['image_path'],
                'price' => $barcodes->min('price'),
                'stock' => $barcodes->first()['stock'],
                'base_unit' => $barcodes->first()['base_unit'],
                'barcodes' => $barcodes->map(fn ($b) => ['barcode' => $b['barcode'], 'unit_name' => $b['unit_name'], 'price' => $b['price']])->values()->all(),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function render()
    {
        return view('livewire.pos.cashier', [
            'heldCarts' => HeldCart::query()->where('user_id', auth()->id())->latest()->get(),
            'searchResultsList' => $this->searchResults(),
            'barcodeProducts' => trim($this->search) === '' ? $this->barcodeProducts() : collect(),
            'cashCategories' => CashCategory::query()->where('type', $this->cashDrawerType)->where('is_active', true)->pluck('name', 'id'),
            'shiftSales' => $this->shiftId
                ? Sale::query()->where('shift_id', $this->shiftId)->latest('sold_at')->get()
                : collect(),
        ]);
    }
}
