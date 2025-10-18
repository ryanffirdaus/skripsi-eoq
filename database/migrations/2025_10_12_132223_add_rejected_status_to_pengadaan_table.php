<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengadaan', function (Blueprint $table) {
            // Modify status enum untuk menambahkan ditolak_procurement dan ditolak_finance
            DB::statement("ALTER TABLE pengadaan MODIFY COLUMN status ENUM('pending', 'disetujui_procurement', 'ditolak_procurement', 'disetujui_finance', 'ditolak_finance', 'diproses', 'diterima', 'dibatalkan') DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengadaan', function (Blueprint $table) {
            // Kembalikan ke enum lama
            DB::statement("ALTER TABLE pengadaan MODIFY COLUMN status ENUM('pending', 'disetujui_procurement', 'disetujui_finance', 'diproses', 'diterima', 'dibatalkan') DEFAULT 'pending'");
        });
    }
};
