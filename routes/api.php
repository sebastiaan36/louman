<?php

use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CustomerPriceController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Support\ApiAbility;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Integratie-API v1
|--------------------------------------------------------------------------
|
| Machine-to-machine koppelvlak voor het externe softwarepakket. Elk token
| hoort bij één koppeling (ApiClient) en draagt zijn eigen rechten; elk
| endpoint controleert het recht dat het nodig heeft.
|
| Schrijfacties vereisen een Idempotency-Key header, zodat een herhaalde
| poging na een timeout nooit twee keer wordt verwerkt.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:sanctum', 'api.client', 'throttle:integration'])
    ->group(function (): void {

        /*
         * Artikelen — het externe pakket is leidend.
         */
        Route::middleware('abilities:'.ApiAbility::ProductsRead)->group(function (): void {
            Route::get('products', [ProductController::class, 'index'])->name('products.index');
            Route::get('products/{product:article_number}', [ProductController::class, 'show'])->name('products.show');
        });

        Route::middleware(['abilities:'.ApiAbility::ProductsWrite, 'api.idempotent'])->group(function (): void {
            Route::put('products/{article_number}', [ProductController::class, 'upsert'])->name('products.upsert');
            Route::patch('products/{product:article_number}/stock', [ProductController::class, 'updateStock'])->name('products.stock');
        });

        /*
         * Klanten — beide richtingen. Aanmelding en goedkeuring blijven van het
         * portaal; stamgegevens en klantnummer komen uit het externe pakket.
         */
        Route::middleware('abilities:'.ApiAbility::CustomersRead)->group(function (): void {
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
            Route::get('customers/{customer}/prices', [CustomerPriceController::class, 'index'])->name('customers.prices.index');
        });

        Route::middleware(['abilities:'.ApiAbility::CustomersWrite, 'api.idempotent'])
            ->patch('customers/{customer}', [CustomerController::class, 'update'])
            ->name('customers.update');

        Route::middleware(['abilities:'.ApiAbility::PricesWrite, 'api.idempotent'])
            ->put('customers/{customer}/prices', [CustomerPriceController::class, 'sync'])
            ->name('customers.prices.sync');

        /*
         * Orders — ontstaan in het portaal, status komt terug uit het pakket.
         */
        Route::middleware('abilities:'.ApiAbility::OrdersRead)->group(function (): void {
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        });

        Route::middleware(['abilities:'.ApiAbility::OrdersWrite, 'api.idempotent'])
            ->patch('orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->name('orders.status');
    });
