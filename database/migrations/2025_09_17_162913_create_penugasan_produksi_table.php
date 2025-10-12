<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan_produksi', function (Blueprint $table) {
            $table->string('penugasan_produksi_id')->primary();

            // Buat kolom string dulu
            $table->string('pengadaan_id');
            $table->string('staf_id');

            $table->integer('jumlah_produksi');
            $table->integer('jumlah_telah_diproduksi')->default(0);
            $table->enum('status', ['Ditugaskan', 'Berjalan', 'Selesai'])->default('Ditugaskan');
            $table->text('catatan')->nullable();

            // Kolom untuk created/updated/deleted_by
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Tambahkan foreign key
            $table->foreign('pengadaan_id')->references('pengadaan_id')->on('pengadaan');
            $table->foreign('staf_id')->references('user_id')->on('users');
            $table->foreign('created_by')->references('user_id')->on('users');
            $table->foreign('updated_by')->references('user_id')->on('users');
            $table->foreign('deleted_by')->references('user_id')->on('users');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('penugasan_produksi');
    }
};
