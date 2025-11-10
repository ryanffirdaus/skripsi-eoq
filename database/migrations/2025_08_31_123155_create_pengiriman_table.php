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
        Schema::create('pengiriman', function (Blueprint $table) {
            $table->string('pengiriman_id', 7)->primary(); // PG001
            $table->string('pesanan_id', 6);
            $table->foreign('pesanan_id')->references('pesanan_id')->on('pesanan')->onDelete('cascade');

            // Informasi Pengiriman
            $table->string('nomor_resi', 30)->unique()->nullable();
            $table->string('kurir', 20); // JNE, J&T, TIKI, POS Indonesia, dll
            $table->decimal('biaya_pengiriman', 15, 2);
            $table->integer('estimasi_hari')->default(1); // estimasi pengiriman dalam hari

            // Status Pengiriman
            $table->enum('status', [
                'pending',      // Menunggu dikirim
                'dikirim',      // Sudah dikirim
                'selesai',    // Terkirim
                'dibatalkan'     // Dibatalkan
            ])->default('pending');

            // Tanggal Penting
            $table->date('tanggal_kirim')->nullable();
            $table->date('tanggal_diterima')->nullable();

            // Catatan
            $table->text('catatan')->nullable();

            // Audit Trail
            $table->string('created_by', 6)->nullable();
            $table->string('updated_by', 6)->nullable();
            $table->string('deleted_by', 6)->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign Keys untuk Audit
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('user_id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['status']);
            $table->index(['kurir']);
            $table->index(['tanggal_kirim']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengiriman');
    }
};
