<?php

use App\Http\Controllers\BusinessSettingController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PermissionManagementController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\StockManagementController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:view dashboard'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('users', UserManagementController::class)->except('show')->middleware('permission:manage users');
    Route::resource('roles', RoleManagementController::class)->except('show')->middleware('permission:manage roles');
    Route::resource('permissions', PermissionManagementController::class)->except('show')->middleware('permission:manage permissions');
    Route::resource('locations', LocationController::class)->except('show')->middleware('permission:manage locations');
    Route::post('/active-location', [LocationController::class, 'switch'])->name('locations.switch');
    Route::resource('brands', BrandController::class)->except('show')->middleware('permission:manage brands');
    Route::resource('categories', CategoryController::class)->except('show')->middleware('permission:manage categories');
    Route::post('/vehicles/quick-store', [VehicleController::class, 'quickStore'])
        ->middleware('permission:manage vehicles')
        ->name('vehicles.quick-store');
    Route::resource('vehicles', VehicleController::class)->middleware('permission:manage vehicles');
    Route::get('/stock-management', [StockManagementController::class, 'index'])
        ->middleware('permission:manage stock')
        ->name('stock.index');
    Route::resource('purchases', PurchaseController::class)->middleware('permission:manage purchases');
    Route::get('/sells/{sell}/invoice', [SellController::class, 'invoice'])
        ->middleware('permission:manage sales')
        ->name('sells.invoice');
    Route::resource('sells', SellController::class)->middleware('permission:manage sales');
    Route::prefix('reports')->name('reports.')->middleware('permission:view reports')->group(function () {
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/purchase-sale', [ReportController::class, 'purchaseSale'])->name('purchase-sale');
        Route::get('/supplier-customer', [ReportController::class, 'supplierCustomer'])->name('supplier-customer');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/trending-products', [ReportController::class, 'trendingProducts'])->name('trending-products');
        Route::get('/items', [ReportController::class, 'items'])->name('items');
        Route::get('/product-purchase', [ReportController::class, 'productPurchase'])->name('product-purchase');
        Route::get('/product-sell', [ReportController::class, 'productSell'])->name('product-sell');
        Route::get('/purchase-payment', [ReportController::class, 'purchasePayment'])->name('purchase-payment');
        Route::get('/sell-payment', [ReportController::class, 'sellPayment'])->name('sell-payment');
        Route::get('/expense', [ReportController::class, 'expense'])->name('expense');
        Route::get('/activity-log', [ReportController::class, 'activityLog'])->name('activity-log');
    });
    Route::get('/business-settings', [BusinessSettingController::class, 'edit'])
        ->middleware('permission:manage business settings')
        ->name('business-settings.edit');
    Route::put('/business-settings', [BusinessSettingController::class, 'update'])
        ->middleware('permission:manage business settings')
        ->name('business-settings.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
