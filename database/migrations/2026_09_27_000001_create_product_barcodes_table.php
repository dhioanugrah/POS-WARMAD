<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra barcodes for a product unit — lets one unit (one name, one
     * price) be scanned via any number of physical barcodes, e.g. 20 loose
     * bottles that each carry a different printed code but sell at the same
     * "Botol" price. `product_units.barcode` remains the primary/first one.
     */
    public function up(): void
    {
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->string('barcode', 50)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
    }
};
