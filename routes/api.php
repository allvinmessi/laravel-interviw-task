<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Products API
Route::prefix('products')->group(function () {
    Route::get('/low-stock', [ProductController::class, 'lowStock'])->name('api.products.low-stock');
    Route::get('/', [ProductController::class, 'index'])->name('api.products.index');
    Route::post('/', [ProductController::class, 'store'])->name('api.products.store');
});

// Orders API
Route::prefix('orders')->group(function () {
    Route::post('/', [OrderController::class, 'store'])->name('api.orders.store');
    Route::get('/', [OrderController::class, 'index'])->name('api.orders.index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('api.orders.show');
});

// Customers API
Route::prefix('customers')->group(function () {
    Route::get('/lookup', [CustomerController::class, 'lookup'])->name('api.customers.lookup');
    Route::get('/orders', [CustomerController::class, 'orders'])->name('api.customers.orders-query');
    Route::get('/{email}/orders', [CustomerController::class, 'orders'])->name('api.customers.orders');
});
