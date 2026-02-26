<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerApprovalController;
use App\Http\Controllers\Admin\DeliveryRouteController;
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
use App\Mail\OrderConfirmation;
use App\Mail\OrderShipped;
use App\Mail\OrderPlacedNotification;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
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
    if ($request->user()->hasVerifiedEmail()) {
        return redirect('/dashboard');
    }

    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/customers/pending', [CustomerApprovalController::class, 'index'])
        ->name('admin.customers.pending');
    Route::get('/customers/export', [CustomerApprovalController::class, 'export'])
        ->name('admin.customers.export');
    Route::post('/customers/import', [CustomerApprovalController::class, 'import'])
        ->name('admin.customers.import');
    Route::get('/customers', [CustomerApprovalController::class, 'allCustomers'])
        ->name('admin.customers.index');
    Route::get('/customers/{customer}', [CustomerApprovalController::class, 'show'])
        ->name('admin.customers.show');
    Route::post('/customers/{customer}/approve', [CustomerApprovalController::class, 'approve'])
        ->name('admin.customers.approve');
    Route::patch('/customers/{customer}/category-discount', [CustomerApprovalController::class, 'updateCategoryAndDiscount'])
        ->name('admin.customers.update-category-discount');
    Route::patch('/customers/{customer}', [CustomerApprovalController::class, 'update'])
        ->name('admin.customers.update');

    // Delivery addresses for customers
    Route::post('/customers/{customer}/delivery-addresses', [CustomerApprovalController::class, 'storeDeliveryAddress'])
        ->name('admin.customers.delivery-addresses.store');
    Route::patch('/customers/{customer}/delivery-addresses/{deliveryAddress}', [CustomerApprovalController::class, 'updateDeliveryAddress'])
        ->name('admin.customers.delivery-addresses.update');
    Route::delete('/customers/{customer}/delivery-addresses/{deliveryAddress}', [CustomerApprovalController::class, 'destroyDeliveryAddress'])
        ->name('admin.customers.delivery-addresses.destroy');

    // Rijroute
    Route::get('/delivery-route', [DeliveryRouteController::class, 'index'])
        ->name('admin.delivery-route');
    Route::post('/delivery-route/order', [DeliveryRouteController::class, 'updateOrder'])
        ->name('admin.delivery-route.update-order');

    Route::resource('categories', CategoryController::class)
        ->except(['show', 'create', 'edit'])
        ->names('admin.categories');
    Route::get('products/export', [ProductController::class, 'export'])->name('admin.products.export');
    Route::post('products/import', [ProductController::class, 'import'])->name('admin.products.import');
    Route::resource('products', ProductController::class)
        ->names('admin.products');

    // Orders
    Route::get('/orders', [AdminOrderController::class, 'index'])
        ->name('admin.orders.index');
    Route::get('/orders/create', [AdminOrderController::class, 'create'])
        ->name('admin.orders.create');
    Route::get('/orders/production-list', [AdminOrderController::class, 'productionList'])
        ->name('admin.orders.production-list');
    Route::get('/orders/customer-overview', [AdminOrderController::class, 'customerOverview'])
        ->name('admin.orders.customer-overview');
    Route::post('/orders', [AdminOrderController::class, 'store'])
        ->name('admin.orders.store');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])
        ->name('admin.orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
        ->name('admin.orders.update-status');
    Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])
        ->name('admin.orders.update');
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

// Mail & PDF previews (alleen voor admins, verwijder na development)
Route::middleware(['auth', 'admin'])->prefix('preview')->group(function () {
    Route::get('/mail/order-placed/{order?}', function (Order $order = null) {
        $order = $order ?? Order::with(['customer.user', 'deliveryAddress', 'items.product'])->latest()->firstOrFail();
        $order->load(['customer.user', 'deliveryAddress', 'items.product']);
        return new OrderPlacedNotification($order);
    })->name('preview.mail.order-placed');

    Route::get('/mail/order-confirmation/{order?}', function (Order $order = null) {
        $order = $order ?? Order::with(['customer.user', 'deliveryAddress', 'items.product'])->latest()->firstOrFail();
        $order->load(['customer.user', 'deliveryAddress', 'items.product']);
        return new OrderConfirmation($order);
    })->name('preview.mail.order-confirmation');

    Route::get('/pdf/packing-slip/{order?}', function (Order $order = null) {
        $order = $order ?? Order::with(['customer.user', 'deliveryAddress', 'items.product'])->latest()->firstOrFail();
        $order->load(['customer.user', 'deliveryAddress', 'items.product']);
        $pdf = Pdf::loadView('pdf.packing-slip', ['order' => $order]);
        return $pdf->stream('pakbon-' . $order->id . '.pdf');
    })->name('preview.pdf.packing-slip');

    Route::get('/mail/order-shipped/{order?}', function (Order $order = null) {
        $order = $order ?? Order::with(['customer.user', 'deliveryAddress', 'items.product'])->latest()->firstOrFail();
        $order->load(['customer.user', 'deliveryAddress', 'items.product']);
        return new OrderShipped($order);
    })->name('preview.mail.order-shipped');
});

require __DIR__.'/settings.php';
