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
        Schema::create('pesanan', function (Blueprint $table) {
            $table->string('pesanan_id', 6)->primary(); // PS001
            $table->string('pelanggan_id', 6);
            $table->date('tanggal_pemesanan');
            $table->decimal('total_harga', 15, 2);
            $table->enum('status', [
                'menunggu',
                'dikonfirmasi',
                'menunggu_pengadaan', // New: Waiting for procurement
                'siap_produksi',      // New: Materials ready
                'sedang_produksi',    // New: In production
                'siap_dikirim',       // New: Production finished / Stock ready
                'dikirim',
                'selesai',
                'dibatalkan'
            ])->default('menunggu');
            $table->text('catatan')->nullable();
            $table->string('dibuat_oleh', 6)->nullable();
            $table->string('diubah_oleh', 6)->nullable();
            $table->string('dihapus_oleh', 6)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('pelanggan_id')->references('pelanggan_id')->on('pelanggan')->onDelete('cascade');
            $table->foreign('dibuat_oleh')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('diubah_oleh')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('dihapus_oleh')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
