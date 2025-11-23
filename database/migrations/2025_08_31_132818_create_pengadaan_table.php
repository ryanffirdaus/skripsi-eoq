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
        Schema::create('pengadaan', function (Blueprint $table) {
            $table->string('pengadaan_id', 6)->primary(); // PA0000001
            $table->enum('jenis_pengadaan', ['pesanan', 'rop']); // Trigger: order-based, ROP-based, or manual
            $table->string('pesanan_id', 6)->nullable(); // Reference to pesanan if triggered by order
            $table->enum('status', ['draft', 'menunggu_persetujuan_gudang', 'menunggu_alokasi_pemasok', 'menunggu_persetujuan_pengadaan', 'menunggu_persetujuan_keuangan', 'diproses', 'diterima', 'dibatalkan', 'ditolak'])
                ->default('draft');
            $table->text('catatan')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->string('ditolak_oleh', 6)->nullable();
            $table->string('dibuat_oleh', 6)->nullable();
            $table->string('diubah_oleh', 6)->nullable();
            $table->string('dihapus_oleh', 6)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('pesanan_id')->references('pesanan_id')->on('pesanan')->onDelete('set null');
            $table->foreign('dibuat_oleh')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('diubah_oleh')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('dihapus_oleh')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('ditolak_oleh')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengadaan');
    }
};
