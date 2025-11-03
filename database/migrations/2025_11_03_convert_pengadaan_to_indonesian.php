<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ubah status pengadaan dan field names ke bahasa Indonesia
     * Status: draft, menunggu_persetujuan_gudang, menunggu_alokasi_pemasok, menunggu_persetujuan_pengadaan, menunggu_persetujuan_keuangan, diproses, diterima, dibatalkan
     * Field: created_by → dibuat_oleh, updated_by → diupdate_oleh, deleted_by → dihapus_oleh, rejected_by → ditolak_oleh
     */
    public function up(): void
    {
        Schema::table('pengadaan', function (Blueprint $table) {
            // Rename columns to Indonesian
            $table->renameColumn('created_by', 'dibuat_oleh');
            $table->renameColumn('updated_by', 'diupdate_oleh');
            $table->renameColumn('deleted_by', 'dihapus_oleh');
            $table->renameColumn('rejected_by', 'ditolak_oleh');
        });

        // Convert status to VARCHAR first to allow updates
        DB::statement("ALTER TABLE pengadaan MODIFY status VARCHAR(50)");

        // Update status values from English to Indonesian
        DB::table('pengadaan')->where('status', 'draft')->update(['status' => 'draft']); // Keep as is
        DB::table('pengadaan')->where('status', 'pending_approval_gudang')->update(['status' => 'menunggu_persetujuan_gudang']);
        DB::table('pengadaan')->where('status', 'pending_supplier_allocation')->update(['status' => 'menunggu_alokasi_pemasok']);
        DB::table('pengadaan')->where('status', 'pending_approval_pengadaan')->update(['status' => 'menunggu_persetujuan_pengadaan']);
        DB::table('pengadaan')->where('status', 'pending_approval_keuangan')->update(['status' => 'menunggu_persetujuan_keuangan']);
        DB::table('pengadaan')->where('status', 'processed')->update(['status' => 'diproses']);
        DB::table('pengadaan')->where('status', 'received')->update(['status' => 'diterima']);
        DB::table('pengadaan')->where('status', 'cancelled')->update(['status' => 'dibatalkan']);

        // Now convert to ENUM with Indonesian values
        DB::statement("ALTER TABLE pengadaan MODIFY status ENUM('draft', 'menunggu_persetujuan_gudang', 'menunggu_alokasi_pemasok', 'menunggu_persetujuan_pengadaan', 'menunggu_persetujuan_keuangan', 'diproses', 'diterima', 'dibatalkan') DEFAULT 'draft'");

        // Update foreign key references due to column rename
        // pengadaan_created_by_foreign → dibuat_oleh
        // pengadaan_updated_by_foreign → diupdate_oleh
        // pengadaan_deleted_by_foreign → dihapus_oleh
        // pengadaan_rejected_by_foreign → ditolak_oleh
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert to VARCHAR first to allow updates
        DB::statement("ALTER TABLE pengadaan MODIFY status VARCHAR(50)");

        // Update status values back to English
        DB::table('pengadaan')->where('status', 'draft')->update(['status' => 'draft']); // Keep as is
        DB::table('pengadaan')->where('status', 'menunggu_persetujuan_gudang')->update(['status' => 'pending_approval_gudang']);
        DB::table('pengadaan')->where('status', 'menunggu_alokasi_pemasok')->update(['status' => 'pending_supplier_allocation']);
        DB::table('pengadaan')->where('status', 'menunggu_persetujuan_pengadaan')->update(['status' => 'pending_approval_pengadaan']);
        DB::table('pengadaan')->where('status', 'menunggu_persetujuan_keuangan')->update(['status' => 'pending_approval_keuangan']);
        DB::table('pengadaan')->where('status', 'diproses')->update(['status' => 'processed']);
        DB::table('pengadaan')->where('status', 'diterima')->update(['status' => 'received']);
        DB::table('pengadaan')->where('status', 'dibatalkan')->update(['status' => 'cancelled']);

        // Revert status ENUM
        DB::statement("ALTER TABLE pengadaan MODIFY status ENUM('draft', 'pending_approval_gudang', 'pending_supplier_allocation', 'pending_approval_pengadaan', 'pending_approval_keuangan', 'processed', 'received', 'cancelled') DEFAULT 'draft'");

        Schema::table('pengadaan', function (Blueprint $table) {
            // Revert column names
            $table->renameColumn('dibuat_oleh', 'created_by');
            $table->renameColumn('diupdate_oleh', 'updated_by');
            $table->renameColumn('dihapus_oleh', 'deleted_by');
            $table->renameColumn('ditolak_oleh', 'rejected_by');
        });
    }
};
