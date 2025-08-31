<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PengirimanController;

use App\Http\Controllers\PengadaanController;

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

    // Pengiriman CRUD routes
    Route::resource('pengiriman', PengirimanController::class);
    Route::patch('pengiriman/{pengiriman}/status', [PengirimanController::class, 'updateStatus'])->name('pengiriman.update-status');

    // Pengadaan CRUD routes
    Route::resource('pengadaan', PengadaanController::class);
    Route::get('pengadaan/dashboard', [PengadaanController::class, 'dashboard'])->name('pengadaan.dashboard');
    Route::post('pengadaan/auto-rop', [PengadaanController::class, 'autoGenerateROP'])->name('pengadaan.auto-rop');
    Route::post('pengadaan/auto-pesanan/{pesanan}', [PengadaanController::class, 'autoGeneratePesanan'])->name('pengadaan.auto-pesanan');
    Route::patch('pengadaan/{pengadaan}/approve', [PengadaanController::class, 'approve'])->name('pengadaan.approve');
    Route::patch('pengadaan/{pengadaan}/status', [PengadaanController::class, 'updateStatus'])->name('pengadaan.update-status');
    Route::post('pengadaan/{pengadaan}/receive', [PengadaanController::class, 'receiveItems'])->name('pengadaan.receive');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
