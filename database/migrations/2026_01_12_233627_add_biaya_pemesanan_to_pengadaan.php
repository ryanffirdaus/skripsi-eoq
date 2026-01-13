<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengadaan_detail', function (Blueprint $table) {
            $table->decimal('biaya_pemesanan', 15, 2)->nullable()->after('harga_satuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengadaan_detail', function (Blueprint $table) {
            $table->dropColumn('biaya_pemesanan');
        });
    }
};
