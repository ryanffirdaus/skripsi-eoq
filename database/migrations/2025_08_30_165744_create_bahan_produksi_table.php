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
        Schema::create('bahan_produksi', function (Blueprint $table) {
            $table->string('produk_id')->nullable();
            $table->string('bahan_baku_id')->nullable();
            $table->integer('jumlah_bahan_baku');

            $table->foreign('produk_id')->references('produk_id')->on('produks')->onDelete('set null');
            $table->foreign('bahan_baku_id')->references('bahan_baku_id')->on('bahan_bakus')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_produksi');
    }
};
