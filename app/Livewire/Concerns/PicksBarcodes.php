<?php

namespace App\Livewire\Concerns;

use App\Models\ProductStock;
use App\Models\ProductUnit;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Barcode dropdown for screens that also accept scanning: one option per
 * physical barcode (an item can have several), hiding barcodes already
 * entered on the screen so only the remaining ones are offered.
 */
trait PicksBarcodes
{
    /** @var array<string, int> barcode => product unit id it was entered for */
    public array $pickedBarcodes = [];

    protected function markBarcodePicked(string $barcode, int $unitId): void
    {
        $this->pickedBarcodes[$barcode] = $unitId;
    }

    /**
     * @param  list<int>  $unitIdsInList  units currently on the screen; picks for units removed since are forgotten
     * @param  Closure(Builder): void|null  $productScope  extra constraints on the product
     * @return Collection<int, array{barcode: string, unit_id: int, product_id: int, product_name: string, product_code: string, image_path: ?string, unit_name: string, price: float, stock: float, base_unit: string}>
     */
    protected function barcodeOptions(?int $locationId, array $unitIdsInList, string $filter = '', ?Closure $productScope = null): Collection
    {
        $this->pickedBarcodes = array_filter($this->pickedBarcodes, fn ($unitId) => in_array($unitId, $unitIdsInList, true));

        $filter = trim($filter);
        $stocks = ProductStock::query()->where('location_id', $locationId)->pluck('qty', 'product_id');

        return ProductUnit::query()
            ->where(fn ($q) => $q->where(fn ($q) => $q->whereNotNull('barcode')->where('barcode', '!=', ''))->orWhereHas('barcodes'))
            ->whereHas('product', function ($q) use ($productScope) {
                $q->where('is_active', true);
                if ($productScope) {
                    $productScope($q);
                }
            })
            ->with(['product', 'barcodes'])
            ->get()
            ->flatMap(fn (ProductUnit $unit) => collect($unit->all_barcodes)->map(fn ($barcode) => [
                'barcode' => $barcode,
                'unit_id' => $unit->id,
                'product_id' => $unit->product_id,
                'product_name' => $unit->product->name,
                'product_code' => $unit->product->code,
                'image_path' => $unit->product->image_path,
                'unit_name' => $unit->unit_name,
                'price' => (float) $unit->price_sell,
                'stock' => (float) ($stocks[$unit->product_id] ?? 0),
                'base_unit' => $unit->product->base_unit,
            ]))
            ->reject(fn ($option) => array_key_exists($option['barcode'], $this->pickedBarcodes))
            ->when($filter !== '', fn ($options) => $options->filter(fn ($option) => str_contains($option['barcode'], $filter)
                || str_contains(mb_strtolower($option['product_name']), mb_strtolower($filter))
                || str_contains(mb_strtolower($option['product_code']), mb_strtolower($filter))))
            ->sortBy([
                fn ($a, $b) => ($a['stock'] > 0) <=> ($b['stock'] > 0),
                fn ($a, $b) => strcasecmp($a['product_name'], $b['product_name']),
                fn ($a, $b) => strcmp($a['barcode'], $b['barcode']),
            ])
            ->values();
    }
}
