<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerApprovalController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Auth\CustomerRegisterController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CustomerProfileController;
use App\Http\Controllers\Customer\DeliveryAddressController;
use App\Http\Controllers\Customer\FavoriteController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PackingSlipController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('/register/customer', [CustomerRegisterController::class, 'create'])
    ->name('customer.register');
Route::post('/register/customer', [CustomerRegisterController::class, 'store'])
    ->name('customer.register.store');

Route::get('/awaiting-approval', fn () => Inertia::render('auth/AwaitingApproval'))
    ->middleware(['auth'])
    ->name('customer.awaiting-approval');

// Email Verification Routes
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/dashboard');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/customers/pending', [CustomerApprovalController::class, 'index'])
        ->name('admin.customers.pending');
    Route::get('/customers', [CustomerApprovalController::class, 'allCustomers'])
        ->name('admin.customers.index');
    Route::get('/customers/{customer}', [CustomerApprovalController::class, 'show'])
        ->name('admin.customers.show');
    Route::post('/customers/{customer}/approve', [CustomerApprovalController::class, 'approve'])
        ->name('admin.customers.approve');
    Route::patch('/customers/{customer}/category-discount', [CustomerApprovalController::class, 'updateCategoryAndDiscount'])
        ->name('admin.customers.update-category-discount');

    Route::resource('categories', CategoryController::class)
        ->except(['show', 'create', 'edit'])
        ->names('admin.categories');
    Route::resource('products', ProductController::class)
        ->names('admin.products');

    // Orders
    Route::get('/orders', [AdminOrderController::class, 'index'])
        ->name('admin.orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])
        ->name('admin.orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
        ->name('admin.orders.update-status');
    Route::get('/orders/{order}/packing-slip', [PackingSlipController::class, 'generate'])
        ->name('admin.orders.packing-slip');

    // Bulk actions
    Route::post('/orders/bulk/update-status', [AdminOrderController::class, 'bulkUpdateStatus'])
        ->name('admin.orders.bulk-update-status');
    Route::post('/orders/bulk/download-packing-slips', [AdminOrderController::class, 'bulkDownloadPackingSlips'])
        ->name('admin.orders.bulk-download-packing-slips');
});

Route::middleware(['auth', 'approved'])->prefix('customer')->group(function () {
    Route::get('/profile', [CustomerProfileController::class, 'edit'])
        ->name('customer.profile');
    Route::patch('/profile', [CustomerProfileController::class, 'update'])
        ->name('customer.profile.update');

    Route::get('/delivery-addresses', [DeliveryAddressController::class, 'index'])
        ->name('customer.delivery-addresses');
    Route::post('/delivery-addresses', [DeliveryAddressController::class, 'store'])
        ->name('customer.delivery-addresses.store');
    Route::patch('/delivery-addresses/{deliveryAddress}', [DeliveryAddressController::class, 'update'])
        ->name('customer.delivery-addresses.update');
    Route::delete('/delivery-addresses/{deliveryAddress}', [DeliveryAddressController::class, 'destroy'])
        ->name('customer.delivery-addresses.destroy');

    // Products
    Route::get('/products', [CustomerProductController::class, 'index'])
        ->name('customer.products');
    Route::get('/products/{product}', [CustomerProductController::class, 'show'])
        ->name('customer.products.show');

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('customer.favorites');
    Route::post('/favorites/{product}/toggle', [FavoriteController::class, 'toggle'])
        ->name('customer.favorites.toggle');

    // Cart
    Route::get('/cart', [CartController::class, 'index'])
        ->name('customer.cart');
    Route::post('/cart/{product}/add', [CartController::class, 'add'])
        ->name('customer.cart.add');
    Route::patch('/cart/{cartItem}', [CartController::class, 'update'])
        ->name('customer.cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'remove'])
        ->name('customer.cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])
        ->name('customer.cart.clear');

    // Orders
    Route::get('/orders', [CustomerOrderController::class, 'index'])
        ->name('customer.orders');
    Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])
        ->name('customer.orders.show');
    Route::post('/orders', [CustomerOrderController::class, 'store'])
        ->name('customer.orders.store');
    Route::get('/orders/{order}/packing-slip', [PackingSlipController::class, 'generate'])
        ->name('customer.orders.packing-slip');
    Route::post('/orders/{order}/reorder', [CartController::class, 'reorder'])
        ->name('customer.orders.reorder');
});

require __DIR__.'/settings.php';
