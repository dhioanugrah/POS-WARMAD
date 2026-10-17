<?php

use App\Livewire\Cash\Categories as CashCategories;
use App\Livewire\Cash\Index as CashIndex;
use App\Livewire\Customers\Index as CustomerIndex;
use App\Livewire\Dashboard;
use App\Livewire\Pos\Cashier;
use App\Livewire\Products\Categories;
use App\Livewire\Products\Form as ProductForm;
use App\Livewire\Products\Index as ProductIndex;
use App\Livewire\Purchases\Form as PurchaseForm;
use App\Livewire\Purchases\Index as PurchaseIndex;
use App\Livewire\Reports\Cash as CashReport;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Reports\ProfitLoss;
use App\Livewire\Reports\Purchases as PurchasesReport;
use App\Livewire\Reports\Sales as SalesReport;
use App\Livewire\Reports\SalesByProduct;
use App\Livewire\Reports\Shifts as ShiftsReport;
use App\Livewire\Reports\StockMovement;
use App\Livewire\Sales\Index as SaleIndex;
use App\Livewire\Sales\Show as SaleShow;
use App\Livewire\Settings\Locations;
use App\Livewire\Settings\Roles;
use App\Livewire\Settings\StoreSettings;
use App\Livewire\Settings\Users;
use App\Livewire\Stock\Card as StockCard;
use App\Livewire\Stock\Minimum as StockMinimum;
use App\Livewire\Stock\Opening as StockOpening;
use App\Livewire\Stock\Position as StockPosition;
use App\Livewire\StockAdjustments\Form as StockAdjustmentForm;
use App\Livewire\StockAdjustments\Index as StockAdjustmentIndex;
use App\Livewire\StockOpnames\Form as StockOpnameForm;
use App\Livewire\StockOpnames\Index as StockOpnameIndex;
use App\Livewire\Suppliers\Index as SupplierIndex;
use App\Models\Sale;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/pos', Cashier::class)
    ->middleware(['auth', 'can:pos.access'])
    ->name('pos.index');

Route::get('/pos/receipt/{sale}', function (Sale $sale) {
    return view('receipts.sale', ['sale' => $sale->load('items', 'payments', 'customer', 'cashier'), 'copy' => request()->boolean('copy')]);
})->middleware(['auth', 'can:pos.access'])->name('pos.receipt');

Route::get('/dashboard', Dashboard::class)
    ->middleware(['auth', 'can:dashboard.view'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/categories', Categories::class)
    ->middleware(['auth', 'can:products.manage'])
    ->name('categories.index');

Route::middleware(['auth'])->prefix('products')->name('products.')->group(function () {
    Route::get('/', ProductIndex::class)->middleware('can:products.view')->name('index');
    Route::get('/create', ProductForm::class)->middleware('can:products.manage')->name('create');
    Route::get('/{product}/edit', ProductForm::class)->middleware('can:products.manage')->name('edit');
});

Route::get('/customers', CustomerIndex::class)
    ->middleware(['auth', 'can:customers.manage'])
    ->name('customers.index');

Route::get('/suppliers', SupplierIndex::class)
    ->middleware(['auth', 'can:suppliers.manage'])
    ->name('suppliers.index');

Route::middleware(['auth', 'can:stock.view'])->prefix('stock')->name('stock.')->group(function () {
    Route::get('/', StockPosition::class)->name('index');
    Route::get('/minimum', StockMinimum::class)->name('minimum');
    Route::get('/card/{product}', StockCard::class)->name('card');
});

Route::get('/stock/opening', StockOpening::class)
    ->middleware(['auth', 'can:stock.opname'])
    ->name('stock.opening');

Route::middleware(['auth', 'can:stock.opname'])->prefix('stock-opnames')->name('stock-opnames.')->group(function () {
    Route::get('/', StockOpnameIndex::class)->name('index');
    Route::get('/create', StockOpnameForm::class)->name('create');
    Route::get('/{stockOpname}', StockOpnameForm::class)->name('show');
});

Route::middleware(['auth', 'can:stock.adjust'])->prefix('stock-adjustments')->name('stock-adjustments.')->group(function () {
    Route::get('/', StockAdjustmentIndex::class)->name('index');
    Route::get('/create', StockAdjustmentForm::class)->name('create');
});

Route::middleware(['auth', 'can:sales.view'])->prefix('sales')->name('sales.')->group(function () {
    Route::get('/', SaleIndex::class)->name('index');
    Route::get('/{sale}', SaleShow::class)->name('show');
});

Route::middleware(['auth', 'can:reports.view'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', ReportsIndex::class)->name('index');
    Route::get('/sales', SalesReport::class)->name('sales');
    Route::get('/sales-by-product', SalesByProduct::class)->name('sales-by-product');
    Route::get('/purchases', PurchasesReport::class)->name('purchases');
    Route::get('/stock-movement', StockMovement::class)->name('stock-movement');
    Route::get('/shifts', ShiftsReport::class)->name('shifts');
    Route::get('/cash', CashReport::class)->name('cash');
    Route::get('/profit-loss', ProfitLoss::class)->name('profit-loss');
});

Route::middleware(['auth', 'can:cash.manage'])->prefix('cash')->name('cash.')->group(function () {
    Route::get('/', CashIndex::class)->name('index');
    Route::get('/categories', CashCategories::class)->name('categories');
});

Route::middleware(['auth'])->prefix('purchases')->name('purchases.')->group(function () {
    Route::get('/', PurchaseIndex::class)->middleware('can:purchases.manage')->name('index');
    Route::get('/create', PurchaseForm::class)->middleware('can:purchases.manage')->name('create');
    Route::get('/{purchase}', PurchaseForm::class)->middleware('can:purchases.manage')->name('show');
});

Route::middleware(['auth', 'can:settings.manage'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', StoreSettings::class)->name('index');
    Route::get('/locations', Locations::class)->name('locations');
});

Route::middleware(['auth', 'can:users.manage'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/users', Users::class)->name('users.index');
    Route::get('/roles', Roles::class)->name('roles');
});

require __DIR__.'/auth.php';
