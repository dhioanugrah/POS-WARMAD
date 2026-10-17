<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The barcodes scanned into each sold line, so a sold barcode (one
     * physical item) is no longer offered in the cashier's item cards.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->json('barcodes')->nullable()->after('unit_name');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('barcodes');
        });
    }
};
