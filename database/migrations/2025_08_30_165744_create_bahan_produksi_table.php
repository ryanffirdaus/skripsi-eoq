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
            $table->string('produk_id', 5)->nullable(); // PP001
            $table->string('bahan_baku_id', 5)->nullable(); // BB001
            $table->integer('jumlah_bahan_baku');

            $table->foreign('produk_id')->references('produk_id')->on('produk')->onDelete('set null');
            $table->foreign('bahan_baku_id')->references('bahan_baku_id')->on('bahan_baku')->onDelete('set null');
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
