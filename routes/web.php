<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\PengadaanController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenerimaanBahanBakuController;
use App\Http\Controllers\PenugasanProduksiController;
use App\Http\Controllers\PemasokController;
use App\Http\Controllers\TransaksiPembayaranController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User CRUD routes
    Route::resource('users', UserController::class);

    // Bahan Baku CRUD routes
    Route::resource('bahan-baku', BahanBakuController::class);

    // Produk CRUD routes
    Route::resource('produk', ProdukController::class);

    // Pelanggan CRUD routes
    Route::resource('pelanggan', PelangganController::class);

    // Pemasok CRUD routes
    Route::resource('pemasok', PemasokController::class);
    Route::post('pemasok/{pemasok_id}/restore', [PemasokController::class, 'restore'])->name('pemasok.restore');

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
    Route::patch('pengadaan/{pengadaan}/reject', [PengadaanController::class, 'reject'])->name('pengadaan.reject');
    Route::patch('pengadaan/{pengadaan}/status', [PengadaanController::class, 'updateStatus'])->name('pengadaan.update-status');
    Route::post('pengadaan/{pengadaan}/receive', [PengadaanController::class, 'receiveItems'])->name('pengadaan.receive');
    Route::post('pengadaan/calculate', [PengadaanController::class, 'calculateProcurement'])->name('pengadaan.calculate');

    // Pembelian (Purchase Order) CRUD routes
    Route::resource('pembelian', PembelianController::class);
    Route::get('pembelian/create/from-pengadaan/{pengadaan}', [PembelianController::class, 'createFromPengadaan'])->name('pembelian.create-from-pengadaan');
    Route::patch('pembelian/{pembelian}/status', [PembelianController::class, 'updateStatus'])->name('pembelian.update-status');
    Route::get('pembelian/{pembelian}/receive', [PembelianController::class, 'showReceiveForm'])->name('pembelian.receive-form');
    Route::post('pembelian/{pembelian}/receive', [PembelianController::class, 'receiveItems'])->name('pembelian.receive');

    // Penerimaan Bahan Baku CRUD routes
    Route::resource('penerimaan-bahan-baku', PenerimaanBahanBakuController::class);
    Route::get('penerimaan/pembelian/{pembelian}/details', [PenerimaanBahanBakuController::class, 'getPembelianDetails'])->name('penerimaan.pembelian-details');

    // Transaksi Pembayaran CRUD routes
    Route::resource('transaksi-pembayaran', TransaksiPembayaranController::class);

    // Penugasan Produksi CRUD routes
    Route::resource('penugasan-produksi', PenugasanProduksiController::class);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
