<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix current database state - force convert all to Indonesian
     */
    public function up(): void
    {
        // Pengadaan: Fix status enum
        DB::statement("ALTER TABLE pengadaan MODIFY status VARCHAR(255)");
        DB::table('pengadaan')->where('status', 'pending_approval_gudang')->update(['status' => 'menunggu_persetujuan_gudang']);
        DB::table('pengadaan')->where('status', 'pending_supplier_allocation')->update(['status' => 'menunggu_alokasi_pemasok']);
        DB::table('pengadaan')->where('status', 'pending_approval_pengadaan')->update(['status' => 'menunggu_persetujuan_pengadaan']);
        DB::table('pengadaan')->where('status', 'pending_approval_keuangan')->update(['status' => 'menunggu_persetujuan_keuangan']);
        DB::table('pengadaan')->where('status', 'processed')->update(['status' => 'diproses']);
        DB::table('pengadaan')->where('status', 'received')->update(['status' => 'diterima']);
        DB::table('pengadaan')->where('status', 'cancelled')->update(['status' => 'dibatalkan']);
        DB::statement("ALTER TABLE pengadaan MODIFY status ENUM('draft', 'menunggu_persetujuan_gudang', 'menunggu_alokasi_pemasok', 'menunggu_persetujuan_pengadaan', 'menunggu_persetujuan_keuangan', 'diproses', 'diterima', 'dibatalkan') DEFAULT 'draft'");

        echo "✅ Pengadaan status fixed\n";
    }

    public function down(): void
    {
        // Revert back
        DB::statement("ALTER TABLE pengadaan MODIFY status VARCHAR(255)");
        DB::table('pengadaan')->where('status', 'menunggu_persetujuan_gudang')->update(['status' => 'pending_approval_gudang']);
        DB::table('pengadaan')->where('status', 'menunggu_alokasi_pemasok')->update(['status' => 'pending_supplier_allocation']);
        DB::table('pengadaan')->where('status', 'menunggu_persetujuan_pengadaan')->update(['status' => 'pending_approval_pengadaan']);
        DB::table('pengadaan')->where('status', 'menunggu_persetujuan_keuangan')->update(['status' => 'pending_approval_keuangan']);
        DB::table('pengadaan')->where('status', 'diproses')->update(['status' => 'processed']);
        DB::table('pengadaan')->where('status', 'diterima')->update(['status' => 'received']);
        DB::table('pengadaan')->where('status', 'dibatalkan')->update(['status' => 'cancelled']);
        DB::statement("ALTER TABLE pengadaan MODIFY status ENUM('draft', 'pending_approval_gudang', 'pending_supplier_allocation', 'pending_approval_pengadaan', 'pending_approval_keuangan', 'processed', 'received', 'cancelled') DEFAULT 'draft'");
    }
};
