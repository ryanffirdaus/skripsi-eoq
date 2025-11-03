<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ubah semua field created_by, updated_by, deleted_by, rejected_by ke bahasa Indonesia
     * di semua tabel yang menggunakan field tersebut
     */
    public function up(): void
    {
        // List of tables that have created_by, updated_by, deleted_by columns
        $tables = [
            'users',
            'bahan_baku',
            'produk',
            'pelanggan',
            'pesanan',
            'pengiriman',
            'pemasok',
            'pembelian',
            'penerimaan_bahan_baku',
            'transaksi_pembayaran',
            'penugasan_produksi',
        ];

        foreach ($tables as $tableName) {
            // Check if table exists
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Rename created_by to dibuat_oleh if exists
                if (Schema::hasColumn($tableName, 'created_by')) {
                    $table->renameColumn('created_by', 'dibuat_oleh');
                }

                // Rename updated_by to diupdate_oleh if exists
                if (Schema::hasColumn($tableName, 'updated_by')) {
                    $table->renameColumn('updated_by', 'diupdate_oleh');
                }

                // Rename deleted_by to dihapus_oleh if exists
                if (Schema::hasColumn($tableName, 'deleted_by')) {
                    $table->renameColumn('deleted_by', 'dihapus_oleh');
                }

                // Special case for pengadaan: rejected_by to ditolak_oleh
                if ($tableName === 'pengadaan' && Schema::hasColumn($tableName, 'rejected_by')) {
                    $table->renameColumn('rejected_by', 'ditolak_oleh');
                }
            });
        }

        // Update enum status for specific tables

        // 1. Pesanan status
        if (Schema::hasTable('pesanan')) {
            // First, convert to VARCHAR to allow updates
            DB::statement("ALTER TABLE pesanan MODIFY COLUMN status VARCHAR(50)");

            // Update existing data
            DB::table('pesanan')->where('status', 'pending')->update(['status' => 'menunggu']);
            DB::table('pesanan')->where('status', 'confirmed')->update(['status' => 'dikonfirmasi']);
            DB::table('pesanan')->where('status', 'processing')->update(['status' => 'diproses']);
            DB::table('pesanan')->where('status', 'ready')->update(['status' => 'siap']);
            DB::table('pesanan')->where('status', 'shipped')->update(['status' => 'dikirim']);
            DB::table('pesanan')->where('status', 'delivered')->update(['status' => 'diterima']);
            DB::table('pesanan')->where('status', 'cancelled')->update(['status' => 'dibatalkan']);
            DB::table('pesanan')->where('status', 'completed')->update(['status' => 'selesai']);

            // Now update to enum with Indonesian values
            DB::statement("ALTER TABLE pesanan MODIFY COLUMN status ENUM('menunggu', 'dikonfirmasi', 'diproses', 'siap', 'dikirim', 'diterima', 'dibatalkan', 'selesai') DEFAULT 'menunggu'");
        }

        // 2. Pengiriman status
        if (Schema::hasTable('pengiriman') && Schema::hasColumn('pengiriman', 'status')) {
            DB::statement("ALTER TABLE pengiriman MODIFY COLUMN status VARCHAR(50)");

            // Map existing Indonesian status values (already in Indonesian!)
            DB::table('pengiriman')->where('status', 'pending')->update(['status' => 'menunggu']);
            DB::table('pengiriman')->where('status', 'in_transit')->update(['status' => 'dalam_perjalanan']);
            DB::table('pengiriman')->where('status', 'delivered')->update(['status' => 'diterima']);
            DB::table('pengiriman')->where('status', 'cancelled')->update(['status' => 'dibatalkan']);
            // Keep existing: dikirim, selesai as is (already Indonesian)

            DB::statement("ALTER TABLE pengiriman MODIFY COLUMN status ENUM('menunggu', 'dalam_perjalanan', 'diterima', 'dikirim', 'selesai', 'dibatalkan') DEFAULT 'menunggu'");
        }

        // 3. TransaksiPembayaran status (NO STATUS COLUMN - skip)
        if (Schema::hasTable('transaksi_pembayaran') && Schema::hasColumn('transaksi_pembayaran', 'status')) {
            DB::statement("ALTER TABLE transaksi_pembayaran MODIFY COLUMN status VARCHAR(50)");

            DB::table('transaksi_pembayaran')->where('status', 'pending')->update(['status' => 'menunggu']);
            DB::table('transaksi_pembayaran')->where('status', 'paid')->update(['status' => 'dibayar']);
            DB::table('transaksi_pembayaran')->where('status', 'cancelled')->update(['status' => 'dibatalkan']);

            DB::statement("ALTER TABLE transaksi_pembayaran MODIFY COLUMN status ENUM('menunggu', 'dibayar', 'dibatalkan') DEFAULT 'menunggu'");
        }

        // 4. PenugasanProduksi status
        if (Schema::hasTable('penugasan_produksi') && Schema::hasColumn('penugasan_produksi', 'status')) {
            DB::statement("ALTER TABLE penugasan_produksi MODIFY COLUMN status VARCHAR(50)");

            // Map actual existing values: ditugaskan, proses, selesai
            DB::table('penugasan_produksi')->where('status', 'pending')->update(['status' => 'menunggu']);
            DB::table('penugasan_produksi')->where('status', 'in_progress')->update(['status' => 'sedang_dikerjakan']);
            DB::table('penugasan_produksi')->where('status', 'completed')->update(['status' => 'selesai']);
            DB::table('penugasan_produksi')->where('status', 'cancelled')->update(['status' => 'dibatalkan']);
            DB::table('penugasan_produksi')->where('status', 'proses')->update(['status' => 'sedang_dikerjakan']); // Fix existing
            // Keep: ditugaskan (similar to menunggu), selesai stays

            DB::statement("ALTER TABLE penugasan_produksi MODIFY COLUMN status ENUM('menunggu', 'ditugaskan', 'sedang_dikerjakan', 'selesai', 'dibatalkan') DEFAULT 'menunggu'");
        }

        // 5. Pembelian status (if exists)
        if (Schema::hasTable('pembelian') && Schema::hasColumn('pembelian', 'status')) {
            DB::statement("ALTER TABLE pembelian MODIFY COLUMN status VARCHAR(50)");

            // Map actual existing values: draft, sent, confirmed
            DB::table('pembelian')->where('status', 'pending')->update(['status' => 'menunggu']);
            DB::table('pembelian')->where('status', 'ordered')->update(['status' => 'dipesan']);
            DB::table('pembelian')->where('status', 'received')->update(['status' => 'diterima']);
            DB::table('pembelian')->where('status', 'cancelled')->update(['status' => 'dibatalkan']);
            DB::table('pembelian')->where('status', 'sent')->update(['status' => 'dikirim']);
            DB::table('pembelian')->where('status', 'confirmed')->update(['status' => 'dikonfirmasi']);
            // Keep draft as is

            DB::statement("ALTER TABLE pembelian MODIFY COLUMN status ENUM('draft', 'menunggu', 'dipesan', 'dikirim', 'dikonfirmasi', 'diterima', 'dibatalkan') DEFAULT 'draft'");
        }

        // 6. PenerimaanBahanBaku status (if exists)
        if (Schema::hasTable('penerimaan_bahan_baku')) {
            if (Schema::hasColumn('penerimaan_bahan_baku', 'status')) {
                DB::statement("ALTER TABLE penerimaan_bahan_baku MODIFY COLUMN status VARCHAR(50)");

                DB::table('penerimaan_bahan_baku')->where('status', 'pending')->update(['status' => 'menunggu']);
                DB::table('penerimaan_bahan_baku')->where('status', 'received')->update(['status' => 'diterima']);
                DB::table('penerimaan_bahan_baku')->where('status', 'cancelled')->update(['status' => 'dibatalkan']);

                DB::statement("ALTER TABLE penerimaan_bahan_baku MODIFY COLUMN status ENUM('menunggu', 'diterima', 'dibatalkan') DEFAULT 'menunggu'");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert field names
        $tables = [
            'users',
            'bahan_baku',
            'produk',
            'pelanggan',
            'pesanan',
            'pengiriman',
            'pemasok',
            'pembelian',
            'penerimaan_bahan_baku',
            'transaksi_pembayaran',
            'penugasan_produksi',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'dibuat_oleh')) {
                    $table->renameColumn('dibuat_oleh', 'created_by');
                }

                if (Schema::hasColumn($tableName, 'diupdate_oleh')) {
                    $table->renameColumn('diupdate_oleh', 'updated_by');
                }

                if (Schema::hasColumn($tableName, 'dihapus_oleh')) {
                    $table->renameColumn('dihapus_oleh', 'deleted_by');
                }

                if ($tableName === 'pengadaan' && Schema::hasColumn($tableName, 'ditolak_oleh')) {
                    $table->renameColumn('ditolak_oleh', 'rejected_by');
                }
            });
        }

        // Revert enum status

        // 1. Pesanan
        if (Schema::hasTable('pesanan')) {
            DB::table('pesanan')->where('status', 'menunggu')->update(['status' => 'pending']);
            DB::table('pesanan')->where('status', 'dikonfirmasi')->update(['status' => 'confirmed']);
            DB::table('pesanan')->where('status', 'diproses')->update(['status' => 'processing']);
            DB::table('pesanan')->where('status', 'siap')->update(['status' => 'ready']);
            DB::table('pesanan')->where('status', 'dikirim')->update(['status' => 'shipped']);
            DB::table('pesanan')->where('status', 'diterima')->update(['status' => 'delivered']);
            DB::table('pesanan')->where('status', 'dibatalkan')->update(['status' => 'cancelled']);
            DB::table('pesanan')->where('status', 'selesai')->update(['status' => 'completed']);

            DB::statement("ALTER TABLE pesanan MODIFY COLUMN status ENUM('pending', 'confirmed', 'processing', 'ready', 'shipped', 'delivered', 'cancelled', 'completed') DEFAULT 'pending'");
        }

        // 2. Pengiriman
        if (Schema::hasTable('pengiriman')) {
            DB::table('pengiriman')->where('status', 'menunggu')->update(['status' => 'pending']);
            DB::table('pengiriman')->where('status', 'dalam_perjalanan')->update(['status' => 'in_transit']);
            DB::table('pengiriman')->where('status', 'diterima')->update(['status' => 'delivered']);
            DB::table('pengiriman')->where('status', 'dibatalkan')->update(['status' => 'cancelled']);

            DB::statement("ALTER TABLE pengiriman MODIFY COLUMN status ENUM('pending', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending'");
        }

        // 3. TransaksiPembayaran
        if (Schema::hasTable('transaksi_pembayaran')) {
            DB::table('transaksi_pembayaran')->where('status', 'menunggu')->update(['status' => 'pending']);
            DB::table('transaksi_pembayaran')->where('status', 'dibayar')->update(['status' => 'paid']);
            DB::table('transaksi_pembayaran')->where('status', 'dibatalkan')->update(['status' => 'cancelled']);

            DB::statement("ALTER TABLE transaksi_pembayaran MODIFY COLUMN status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending'");
        }

        // 4. PenugasanProduksi
        if (Schema::hasTable('penugasan_produksi')) {
            DB::table('penugasan_produksi')->where('status', 'menunggu')->update(['status' => 'pending']);
            DB::table('penugasan_produksi')->where('status', 'sedang_dikerjakan')->update(['status' => 'in_progress']);
            DB::table('penugasan_produksi')->where('status', 'selesai')->update(['status' => 'completed']);
            DB::table('penugasan_produksi')->where('status', 'dibatalkan')->update(['status' => 'cancelled']);

            DB::statement("ALTER TABLE penugasan_produksi MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending'");
        }

        // 5. Pembelian
        if (Schema::hasTable('pembelian') && Schema::hasColumn('pembelian', 'status')) {
            DB::table('pembelian')->where('status', 'menunggu')->update(['status' => 'pending']);
            DB::table('pembelian')->where('status', 'dipesan')->update(['status' => 'ordered']);
            DB::table('pembelian')->where('status', 'diterima')->update(['status' => 'received']);
            DB::table('pembelian')->where('status', 'dibatalkan')->update(['status' => 'cancelled']);

            DB::statement("ALTER TABLE pembelian MODIFY COLUMN status ENUM('pending', 'ordered', 'received', 'cancelled') DEFAULT 'pending'");
        }

        // 6. PenerimaanBahanBaku
        if (Schema::hasTable('penerimaan_bahan_baku') && Schema::hasColumn('penerimaan_bahan_baku', 'status')) {
            DB::table('penerimaan_bahan_baku')->where('status', 'menunggu')->update(['status' => 'pending']);
            DB::table('penerimaan_bahan_baku')->where('status', 'diterima')->update(['status' => 'received']);
            DB::table('penerimaan_bahan_baku')->where('status', 'dibatalkan')->update(['status' => 'cancelled']);

            DB::statement("ALTER TABLE penerimaan_bahan_baku MODIFY COLUMN status ENUM('pending', 'received', 'cancelled') DEFAULT 'pending'");
        }
    }
};
