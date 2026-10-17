<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class StoreSettings extends Component
{
    use WithFileUploads;

    public string $storeName = '';

    public string $storeAddress = '';

    public string $storePhone = '';

    public string $storeTaxId = '';

    public ?string $logoPath = null;

    public $newLogo = null;

    public int $paperWidth = 58;

    public string $receiptFooter = '';

    public bool $showLogo = false;

    public int $copies = 1;

    public bool $autoPrint = true;

    public int $rounding = 0;

    public bool $allowNegativeStock = true;

    public int $maxCashierDiscountPercent = 10;

    public array $docPrefixes = [];

    public array $docTypeLabels = [
        'sale' => 'Penjualan (PJ)',
        'sale_return' => 'Retur Jual (RJ)',
        'purchase' => 'Pembelian (PB)',
        'purchase_return' => 'Retur Beli (RB)',
        'transfer' => 'Transfer (TR)',
        'opname' => 'Opname (OP)',
        'adjustment' => 'Penyesuaian (PS)',
        'cash_in' => 'Kas Masuk (KM)',
        'cash_out' => 'Kas Keluar (KK)',
    ];

    public function mount(): void
    {
        $this->storeName = setting('store.name', '');
        $this->storeAddress = setting('store.address', '');
        $this->storePhone = setting('store.phone', '');
        $this->storeTaxId = setting('store.tax_id', '');
        $this->logoPath = setting('store.logo_path');

        $this->paperWidth = (int) setting('receipt.paper_width', 58);
        $this->receiptFooter = setting('receipt.footer', '');
        $this->showLogo = (bool) setting('receipt.show_logo', false);
        $this->copies = (int) setting('receipt.copies', 1);
        $this->autoPrint = (bool) setting('receipt.auto_print', true);

        $this->rounding = (int) setting('pos.rounding', 0);
        $this->allowNegativeStock = (bool) setting('pos.allow_negative_stock', true);
        $this->maxCashierDiscountPercent = (int) setting('pos.max_cashier_discount_percent', 10);

        foreach (array_keys($this->docTypeLabels) as $type) {
            $this->docPrefixes[$type] = setting("doc.prefix.{$type}", strtoupper(substr($type, 0, 2)));
        }
    }

    public function rules(): array
    {
        return [
            'storeName' => 'required|string|max:150',
            'storeAddress' => 'nullable|string|max:255',
            'storePhone' => 'nullable|string|max:30',
            'storeTaxId' => 'nullable|string|max:30',
            'newLogo' => 'nullable|image|max:1024',
            'paperWidth' => 'required|in:58,80',
            'receiptFooter' => 'nullable|string|max:500',
            'copies' => 'required|integer|min:1|max:5',
            'rounding' => 'required|in:0,100,500,1000',
            'maxCashierDiscountPercent' => 'required|integer|min:0|max:100',
            'docPrefixes.*' => 'required|string|max:5',
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->newLogo) {
            $this->logoPath = $this->newLogo->store('logos', 'public');
        }

        setting_set('store.name', $this->storeName);
        setting_set('store.address', $this->storeAddress);
        setting_set('store.phone', $this->storePhone);
        setting_set('store.tax_id', $this->storeTaxId);
        setting_set('store.logo_path', $this->logoPath);

        setting_set('receipt.paper_width', $this->paperWidth);
        setting_set('receipt.footer', $this->receiptFooter);
        setting_set('receipt.show_logo', $this->showLogo);
        setting_set('receipt.copies', $this->copies);
        setting_set('receipt.auto_print', $this->autoPrint);

        setting_set('pos.rounding', $this->rounding);
        setting_set('pos.allow_negative_stock', $this->allowNegativeStock);
        setting_set('pos.max_cashier_discount_percent', $this->maxCashierDiscountPercent);

        foreach ($this->docPrefixes as $type => $prefix) {
            setting_set("doc.prefix.{$type}", strtoupper($prefix));
        }

        $this->newLogo = null;

        $this->dispatch('notify', message: 'Pengaturan tersimpan.', type: 'success');
    }

    public function removeLogo(): void
    {
        if ($this->logoPath) {
            Storage::disk('public')->delete($this->logoPath);
        }

        $this->logoPath = null;
        setting_set('store.logo_path', null);
        $this->dispatch('notify', message: 'Logo dihapus.', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.store-settings');
    }
}
