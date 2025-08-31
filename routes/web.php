<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PesananController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // User CRUD routes
    Route::resource('users', UserController::class);

    // Bahan Baku CRUD routes
    Route::resource('bahan-baku', BahanBakuController::class);

    // Produk CRUD routes
    Route::resource('produk', ProdukController::class);

    // Pelanggan CRUD routes
    Route::resource('pelanggan', PelangganController::class);

    // Pesanan CRUD routes
    Route::resource('pesanan', PesananController::class);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
