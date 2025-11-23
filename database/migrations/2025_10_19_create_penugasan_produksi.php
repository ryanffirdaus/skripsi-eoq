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
        Schema::dropIfExists('penugasan_produksi');

        Schema::create('penugasan_produksi', function (Blueprint $table) {
            $table->string('penugasan_id', 6)->primary(); // PT0000001
            $table->string('pengadaan_detail_id', 8);
            $table->foreign('pengadaan_detail_id')->references('pengadaan_detail_id')->on('pengadaan_detail')->onDelete('cascade');

            // user_id: siapa yang ditugaskan
            $table->string('user_id', 6);
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');

            // Jumlah yang harus diproduksi
            $table->integer('jumlah_produksi');

            // Status: ditugaskan, proses, selesai, dibatalkan
            $table->enum('status', ['ditugaskan', 'proses', 'selesai', 'dibatalkan'])->default('ditugaskan');

            $table->date('deadline');
            $table->text('catatan')->nullable();

            // dibuat_oleh: siapa yang menugaskan
            $table->string('dibuat_oleh', 6)->nullable();
            $table->foreign('dibuat_oleh')->references('user_id')->on('users')->onDelete('set null');

            // diubah_oleh: siapa yang terakhir update
            $table->string('diubah_oleh', 6)->nullable();
            $table->foreign('diubah_oleh')->references('user_id')->on('users')->onDelete('set null');

            // dihapus_oleh: siapa yang menghapus
            $table->string('dihapus_oleh', 6)->nullable();
            $table->foreign('dihapus_oleh')->references('user_id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('pengadaan_detail_id');
            $table->index('user_id');
            $table->index('status');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penugasan_produksi');
    }
};
