<?php

namespace App\Livewire\Reports;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public function render()
    {
        $reports = collect([
            ['label' => 'Penjualan', 'route' => 'reports.sales', 'permission' => 'reports.view', 'icon' => 'receipt', 'description' => 'Ringkasan per hari, kasir, metode bayar, pelanggan.'],
            ['label' => 'Penjualan per Barang', 'route' => 'reports.sales-by-product', 'permission' => 'reports.view', 'icon' => 'cube', 'description' => 'Qty, omzet, HPP, laba, margin per barang.'],
            ['label' => 'Pembelian', 'route' => 'reports.purchases', 'permission' => 'reports.view', 'icon' => 'truck', 'description' => 'Pembelian per supplier / barang.'],
            ['label' => 'Mutasi Stok', 'route' => 'reports.stock-movement', 'permission' => 'reports.view', 'icon' => 'archive-box', 'description' => 'Awal, masuk, keluar, akhir per barang.'],
            ['label' => 'Laporan Shift', 'route' => 'reports.shifts', 'permission' => 'reports.view', 'icon' => 'clipboard-document-check', 'description' => 'Modal, penjualan per metode, selisih kas.'],
            ['label' => 'Kas Masuk/Keluar', 'route' => 'reports.cash', 'permission' => 'reports.view', 'icon' => 'banknotes', 'description' => 'Rekap kas per kategori.'],
            ['label' => 'Laba Rugi Sederhana', 'route' => 'reports.profit-loss', 'permission' => 'reports.view', 'icon' => 'document-chart-bar', 'description' => 'Penjualan − HPP − Biaya = Laba bersih.'],
        ])->filter(fn ($r) => auth()->user()->can($r['permission']))
            ->filter(fn ($r) => ! auth()->user()->hasRole('Gudang') || $r['route'] === 'reports.stock-movement')
            ->values();

        return view('livewire.reports.index', ['reports' => $reports]);
    }
}
