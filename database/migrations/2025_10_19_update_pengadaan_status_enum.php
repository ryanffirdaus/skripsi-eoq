<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Update pengadaan status enum dengan status flow baru:
     * draft → pending_approval_gudang → pending_supplier_allocation → pending_approval_pengadaan → pending_approval_keuangan → processed → received
     */
    public function up(): void
    {
        // Update existing data first
        // Map old status ke new status
        DB::table('pengadaan')->where('status', 'pending')->update(['status' => 'draft']);
        DB::table('pengadaan')->where('status', 'disetujui_pengadaan')->update(['status' => 'pending_approval_gudang']);
        DB::table('pengadaan')->where('status', 'disetujui_keuangan')->update(['status' => 'pending_approval_keuangan']);

        // Modify the column using raw SQL for MySQL compatibility
        Schema::table('pengadaan', function (Blueprint $table) {
            // Using raw SQL to change enum type
            $table->string('status')->change();
        });

        // Now set the correct values
        DB::statement("ALTER TABLE pengadaan MODIFY status ENUM('draft', 'pending_approval_gudang', 'pending_supplier_allocation', 'pending_approval_pengadaan', 'pending_approval_keuangan', 'processed', 'received', 'cancelled') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to old enum values
        DB::table('pengadaan')->where('status', 'draft')->update(['status' => 'pending']);
        DB::table('pengadaan')->where('status', 'pending_approval_gudang')->update(['status' => 'disetujui_pengadaan']);
        DB::table('pengadaan')->where('status', 'pending_approval_keuangan')->update(['status' => 'disetujui_keuangan']);

        Schema::table('pengadaan', function (Blueprint $table) {
            $table->string('status')->change();
        });

        DB::statement("ALTER TABLE pengadaan MODIFY status ENUM('pending', 'disetujui_pengadaan', 'disetujui_keuangan', 'diproses', 'diterima', 'dibatalkan') DEFAULT 'pending'");
    }
};
