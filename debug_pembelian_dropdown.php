<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Pembelian;
use App\Models\TransaksiPembayaran;

echo "=== DEBUG DROPDOWN PEMBELIAN ===\n\n";

// 1. Cek semua pembelian dengan status yang valid
$allPembelians = Pembelian::with('pemasok:pemasok_id,nama_pemasok')
    ->whereIn('status', ['dikonfirmasi', 'dipesan', 'dikirim', 'diterima'])
    ->select('pembelian_id', 'pemasok_id', 'total_biaya', 'tanggal_pembelian', 'metode_pembayaran', 'termin_pembayaran', 'jumlah_dp')
    ->orderBy('tanggal_pembelian', 'desc')
    ->get();

echo "Total Pembelian dengan status valid: " . $allPembelians->count() . "\n\n";

foreach ($allPembelians as $pembelian) {
    echo "PO: {$pembelian->pembelian_id}\n";
    echo "  Status: {$pembelian->status}\n";
    echo "  Pemasok: " . ($pembelian->pemasok->nama_pemasok ?? 'N/A') . "\n";
    echo "  Total Biaya: Rp " . number_format($pembelian->total_biaya, 0, ',', '.') . "\n";

    // Cek total dibayar
    $totalDibayar = TransaksiPembayaran::where('pembelian_id', $pembelian->pembelian_id)
        ->sum('total_pembayaran');

    echo "  Total Dibayar: Rp " . number_format($totalDibayar, 0, ',', '.') . "\n";

    $sisaPembayaran = $pembelian->total_biaya - $totalDibayar;
    echo "  Sisa Pembayaran: Rp " . number_format($sisaPembayaran, 0, ',', '.') . "\n";

    echo "  Accessor total_dibayar: Rp " . number_format($pembelian->total_dibayar, 0, ',', '.') . "\n";
    echo "  Accessor sisa_pembayaran: Rp " . number_format($pembelian->sisa_pembayaran, 0, ',', '.') . "\n";

    // Cek apakah akan muncul di dropdown
    if ((float) $pembelian->sisa_pembayaran > 0) {
        echo "  ✅ MUNCUL DI DROPDOWN\n";
    } else {
        echo "  ❌ TIDAK MUNCUL (sudah lunas)\n";
    }

    echo "\n";
}

// 2. Cek transaksi pembayaran
$transaksiCount = TransaksiPembayaran::count();
echo "\n=== TRANSAKSI PEMBAYARAN ===\n";
echo "Total Transaksi: {$transaksiCount}\n\n";

if ($transaksiCount > 0) {
    $transaksis = TransaksiPembayaran::with('pembelian')->get();
    foreach ($transaksis as $t) {
        echo "Transaksi: {$t->transaksi_pembayaran_id}\n";
        echo "  PO: {$t->pembelian_id}\n";
        echo "  Jumlah: Rp " . number_format($t->total_pembayaran, 0, ',', '.') . "\n";
        echo "  Jenis: {$t->jenis_pembayaran}\n\n";
    }
}

echo "\n=== SELESAI ===\n";
