<?php

use Illuminate\Support\Facades\Route;
use App\Models\Pembelian;
use App\Models\TransaksiPembayaran;

Route::get('/debug-dropdown', function () {
    $allPembelians = Pembelian::with('pemasok:pemasok_id,nama_pemasok')
        ->whereIn('status', ['dikonfirmasi', 'dipesan', 'dikirim', 'diterima'])
        ->orderBy('tanggal_pembelian', 'desc')
        ->get();

    $result = [];

    foreach ($allPembelians as $pembelian) {
        $totalDibayar = TransaksiPembayaran::where('pembelian_id', $pembelian->pembelian_id)
            ->sum('total_pembayaran');

        $sisaPembayaran = (float)$pembelian->total_biaya - (float)$totalDibayar;

        $result[] = [
            'pembelian_id' => $pembelian->pembelian_id,
            'status' => $pembelian->status,
            'pemasok' => $pembelian->pemasok->nama_pemasok ?? 'N/A',
            'total_biaya' => number_format($pembelian->total_biaya, 0, ',', '.'),
            'total_dibayar' => number_format($totalDibayar, 0, ',', '.'),
            'sisa_pembayaran' => number_format($sisaPembayaran, 0, ',', '.'),
            'accessor_total_dibayar' => number_format($pembelian->total_dibayar, 0, ',', '.'),
            'accessor_sisa_pembayaran' => number_format($pembelian->sisa_pembayaran, 0, ',', '.'),
            'akan_muncul' => $sisaPembayaran > 0 ? 'YA' : 'TIDAK (sudah lunas)',
        ];
    }

    return response()->json([
        'total_po' => $allPembelians->count(),
        'detail' => $result,
        'transaksi_pembayaran' => TransaksiPembayaran::with('pembelian:pembelian_id')->get()->map(function ($t) {
            return [
                'id' => $t->transaksi_pembayaran_id,
                'pembelian_id' => $t->pembelian_id,
                'jumlah' => number_format($t->total_pembayaran, 0, ',', '.'),
                'jenis' => $t->jenis_pembayaran,
            ];
        })
    ], 200, [], JSON_PRETTY_PRINT);
});
