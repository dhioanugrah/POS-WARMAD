<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Default setting values per Tech Spec §3. Idempotent: only fills keys that
 * don't exist yet so re-running seeders never clobbers Owner changes.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'store.name' => 'Pute Mart',
            'store.address' => '',
            'store.phone' => '',
            'store.logo_path' => null,
            'store.tax_id' => '',
            'receipt.paper_width' => 58,
            'receipt.footer' => "Barang yang telah dibeli tidak dapat dikembalikan kecuali ada perjanjian\nTerima kasih atas kunjungan anda",
            'receipt.show_logo' => false,
            'receipt.copies' => 1,
            'receipt.auto_print' => true,
            'pos.rounding' => 0,
            'pos.allow_negative_stock' => true,
            'pos.max_cashier_discount_percent' => 10,
            'pos.customer_display_text' => 'Selamat Berbelanja di Toko Kami',
            'tax.enabled' => false,
            'tax.rate' => 11,
            'tax.inclusive' => true,
            'doc.prefix.sale' => 'PJ',
            'doc.prefix.sale_return' => 'RJ',
            'doc.prefix.purchase' => 'PB',
            'doc.prefix.purchase_return' => 'RB',
            'doc.prefix.transfer' => 'TR',
            'doc.prefix.opname' => 'OP',
            'doc.prefix.adjustment' => 'PS',
            'doc.prefix.cash_in' => 'KM',
            'doc.prefix.cash_out' => 'KK',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
