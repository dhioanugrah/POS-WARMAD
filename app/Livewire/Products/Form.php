<?php

namespace App\Livewire\Products;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Form extends Component
{
    use WithFileUploads;

    public ?int $productId = null;

    public string $code = '';

    public string $name = '';

    public ?int $categoryId = null;

    public ?int $brandId = null;

    public bool $showQuickAddCategory = false;

    public string $quickAddCategoryName = '';

    public bool $showQuickAddBrand = false;

    public string $quickAddBrandName = '';

    public string $type = 'stock';

    public $minStock = 0;

    public bool $isActive = true;

    public string $notes = '';

    public $newImage = null;

    public ?string $imagePath = null;

    /**
     * One row per SATUAN (unit): name + conversion + price defined once,
     * but `barcodes` can hold any number of physical barcodes that all
     * resolve to that same unit/price — scan 20 loose bottles into one
     * "Botol" row instead of creating 20 unit rows.
     *
     * @var array<int, array{id: ?int, unit_name: string, conversion_factor: mixed, barcodes: list<string>, barcode_input: string, price_sell: mixed, is_default_sale: bool}>
     */
    public array $units = [];

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->productId = $product->id;
            $this->code = $product->code;
            $this->name = $product->name;
            $this->categoryId = $product->category_id;
            $this->brandId = $product->brand_id;
            $this->type = $product->type->value;
            $this->minStock = (string) $product->min_stock;
            $this->isActive = $product->is_active;
            $this->notes = (string) $product->notes;
            $this->imagePath = $product->image_path;

            $this->units = $product->units()->with('barcodes')->orderByDesc('is_base')->get()->map(fn (ProductUnit $u) => [
                'id' => $u->id,
                'unit_name' => $u->unit_name,
                'conversion_factor' => (string) $u->conversion_factor,
                'barcodes' => $u->all_barcodes,
                'barcode_input' => '',
                'price_sell' => (string) $u->price_sell,
                'is_default_sale' => $u->is_default_sale,
            ])->all();
        } else {
            $this->units = [
                ['id' => null, 'unit_name' => 'Pcs', 'conversion_factor' => '1', 'barcodes' => [], 'barcode_input' => '', 'price_sell' => '', 'is_default_sale' => true],
            ];
        }
    }

    public function openQuickAddCategory(): void
    {
        $this->quickAddCategoryName = '';
        $this->showQuickAddCategory = true;
    }

    public function quickAddCategory(): void
    {
        $this->validate([
            'quickAddCategoryName' => ['required', 'string', 'max:150', Rule::unique('categories', 'name')],
        ], [], ['quickAddCategoryName' => 'nama kategori']);

        $category = Category::create(['name' => $this->quickAddCategoryName]);

        $this->categoryId = $category->id;
        $this->showQuickAddCategory = false;
        $this->dispatch('notify', message: 'Kategori ditambahkan.', type: 'success');
    }

    public function openQuickAddBrand(): void
    {
        $this->quickAddBrandName = '';
        $this->showQuickAddBrand = true;
    }

    public function quickAddBrand(): void
    {
        $this->validate([
            'quickAddBrandName' => ['required', 'string', 'max:150', Rule::unique('brands', 'name')],
        ], [], ['quickAddBrandName' => 'nama merek']);

        $brand = Brand::create(['name' => $this->quickAddBrandName]);

        $this->brandId = $brand->id;
        $this->showQuickAddBrand = false;
        $this->dispatch('notify', message: 'Merek ditambahkan.', type: 'success');
    }

    public function addUnit(): void
    {
        $this->units[] = ['id' => null, 'unit_name' => '', 'conversion_factor' => '', 'barcodes' => [], 'barcode_input' => '', 'price_sell' => '', 'is_default_sale' => false];
    }

    public function removeUnit(int $index): void
    {
        if ($index === 0) {
            return; // base unit (row 0) can't be removed
        }
        unset($this->units[$index]);
        $this->units = array_values($this->units);
    }

    /** Scan/type a barcode into a unit row + Enter: adds it to that row's list. */
    public function addBarcode(int $unitIndex): void
    {
        $value = trim($this->units[$unitIndex]['barcode_input'] ?? '');
        $this->units[$unitIndex]['barcode_input'] = '';

        if ($value === '') {
            return;
        }

        $allBarcodesInForm = collect($this->units)->flatMap(fn ($u) => $u['barcodes'])->all();
        if (in_array($value, $allBarcodesInForm, true)) {
            $this->addError('units', "Barcode {$value} sudah dipakai di barang ini juga.");

            return;
        }

        $this->units[$unitIndex]['barcodes'][] = $value;
    }

    public function removeBarcode(int $unitIndex, int $barcodeIndex): void
    {
        unset($this->units[$unitIndex]['barcodes'][$barcodeIndex]);
        $this->units[$unitIndex]['barcodes'] = array_values($this->units[$unitIndex]['barcodes']);
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', Rule::unique('products', 'code')->ignore($this->productId)],
            'name' => 'required|string|max:150',
            'categoryId' => 'nullable|exists:categories,id',
            'brandId' => 'nullable|exists:brands,id',
            'type' => 'required|in:stock,service',
            'minStock' => 'required|numeric|min:0',
            'newImage' => 'nullable|image|max:2048',
            'units' => 'required|array|min:1',
            'units.*.unit_name' => 'required|string|max:20',
            'units.*.conversion_factor' => 'required|numeric|min:0.001',
            'units.*.price_sell' => 'required|numeric|min:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'units.*.unit_name.required' => 'Nama satuan wajib diisi.',
            'units.*.price_sell.required' => 'Harga jual wajib diisi.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        // Barcode uniqueness across ALL product_units + product_barcodes (excluding this product's own rows).
        $barcodes = collect($this->units)->flatMap(fn ($u) => $u['barcodes'])->all();
        if (count($barcodes) !== count(array_unique($barcodes))) {
            $this->addError('units', 'Ada barcode yang sama dipakai dua kali di barang ini.');

            return;
        }

        foreach ($barcodes as $barcode) {
            $existingUnit = ProductUnit::query()
                ->whereAnyBarcode($barcode)
                ->when($this->productId, fn ($q) => $q->where('product_id', '!=', $this->productId))
                ->with('product')
                ->first();

            if ($existingUnit) {
                $this->addError('units', "Barcode {$barcode} sudah dipakai oleh {$existingUnit->product->name} ({$existingUnit->unit_name}).");

                return;
            }
        }

        DB::transaction(function () {
            $code = $this->code ?: $this->generateCode();

            if ($this->newImage) {
                $this->imagePath = $this->newImage->store('products', 'public');
            }

            $product = Product::query()->updateOrCreate(
                ['id' => $this->productId],
                [
                    'code' => $code,
                    'name' => $this->name,
                    'category_id' => $this->categoryId,
                    'brand_id' => $this->brandId,
                    'type' => $this->type,
                    'base_unit' => $this->units[0]['unit_name'],
                    'min_stock' => $this->minStock,
                    'image_path' => $this->imagePath,
                    'notes' => $this->notes,
                    'is_active' => $this->isActive,
                ]
            );

            $keptIds = [];

            foreach ($this->units as $index => $unit) {
                $barcodeList = $unit['barcodes'];

                $saved = ProductUnit::query()->updateOrCreate(
                    ['id' => $unit['id'] ?? null, 'product_id' => $product->id],
                    [
                        'unit_name' => $unit['unit_name'],
                        'conversion_factor' => $unit['conversion_factor'],
                        'is_base' => $index === 0,
                        'barcode' => $barcodeList[0] ?? null,
                        'price_sell' => $unit['price_sell'],
                        'is_default_sale' => $index === 0,
                    ]
                );
                $keptIds[] = $saved->id;

                $saved->barcodes()->delete();
                foreach (array_slice($barcodeList, 1) as $extraBarcode) {
                    ProductBarcode::create(['product_unit_id' => $saved->id, 'barcode' => $extraBarcode]);
                }
            }

            $product->units()->whereNotIn('id', $keptIds)->delete();

            $this->productId = $product->id;
        });

        $this->dispatch('notify', message: 'Barang tersimpan.', type: 'success');
        $this->redirect(route('products.index'), navigate: true);
    }

    private function generateCode(): string
    {
        $last = Product::query()->where('code', 'like', 'BRG-%')->orderByDesc('id')->value('code');
        $next = $last ? ((int) substr($last, 4)) + 1 : 1;

        return sprintf('BRG-%05d', $next);
    }

    public function render()
    {
        return view('livewire.products.form', [
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id'),
            'brands' => Brand::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
