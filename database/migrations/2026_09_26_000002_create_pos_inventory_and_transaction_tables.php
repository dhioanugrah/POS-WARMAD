<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150)->index();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->restrictOnDelete();
            $table->enum('type', ['stock', 'service'])->default('stock');
            $table->string('base_unit', 20);
            $table->decimal('min_stock', 15, 3)->default(0);
            $table->decimal('avg_cost', 15, 2)->default(0);
            $table->decimal('last_purchase_cost', 15, 2)->default(0);
            $table->string('image_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('unit_name', 20);
            $table->decimal('conversion_factor', 15, 3)->default(1);
            $table->boolean('is_base')->default(false);
            $table->string('barcode', 50)->nullable()->unique();
            $table->decimal('price_sell', 15, 2);
            $table->boolean('is_default_sale')->default(false);
            $table->unique(['product_id', 'unit_name']);
            $table->timestamps();
        });

        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->decimal('min_qty', 15, 3);
            $table->decimal('price', 15, 2);
            $table->unique(['product_unit_id', 'min_qty']);
            $table->timestamps();
        });

        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->decimal('qty', 15, 3)->default(0);
            $table->unique(['product_id', 'location_id']);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('qty_change', 15, 3);
            $table->decimal('qty_after', 15, 3);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->nullableMorphs('reference');
            $table->string('note')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['product_id', 'location_id', 'occurred_at']);
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->foreignId('scope_category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('system_qty', 15, 3);
            $table->decimal('counted_qty', 15, 3)->nullable();
            $table->decimal('difference', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->unique(['stock_opname_id', 'product_id']);
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->enum('reason', ['damaged', 'lost', 'expired', 'own_use', 'other']);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('adjusted_at');
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_unit_id')->constrained('product_units')->restrictOnDelete();
            $table->decimal('qty', 15, 3);
            $table->decimal('qty_base', 15, 3);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->timestamp('opened_at');
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->decimal('counted_cash', 15, 2)->nullable();
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('difference', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->timestamp('sold_at');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->enum('discount_type', ['amount', 'percent'])->nullable();
            $table->decimal('discount_value', 15, 2)->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('rounding_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('unpaid');
            $table->date('due_date')->nullable();
            $table->enum('status', ['completed', 'void'])->default('completed');
            $table->decimal('cost_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_unit_id')->constrained('product_units')->restrictOnDelete();
            $table->string('product_name', 150);
            $table->string('unit_name', 20);
            $table->decimal('conversion_factor', 15, 3)->default(1);
            $table->decimal('qty', 15, 3)->default(0);
            $table->decimal('qty_base', 15, 3)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->enum('discount_type', ['amount', 'percent'])->nullable();
            $table->decimal('discount_value', 15, 2)->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->boolean('price_overridden')->default(false);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');
            $table->enum('direction', ['in', 'out']);
            $table->string('method', 20);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('reference')->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->boolean('is_initial')->default(false);
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('held_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->string('label', 100);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->json('payload');
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('supplier_invoice_no')->nullable();
            $table->timestamp('purchased_at');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('other_cost', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('unpaid');
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_unit_id')->constrained('product_units')->restrictOnDelete();
            $table->string('unit_name', 20);
            $table->decimal('conversion_factor', 15, 3)->default(1);
            $table->decimal('qty', 15, 3)->default(0);
            $table->decimal('qty_base', 15, 3)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('landed_unit_cost', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->restrictOnDelete();
            $table->foreignId('cash_category_id')->constrained('cash_categories')->restrictOnDelete();
            $table->enum('type', ['in', 'out']);
            $table->enum('method', ['cash', 'bank']);
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamp('occurred_at');
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('held_carts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_stocks');
        Schema::dropIfExists('product_price_tiers');
        Schema::dropIfExists('product_units');
        Schema::dropIfExists('products');
    }
};
