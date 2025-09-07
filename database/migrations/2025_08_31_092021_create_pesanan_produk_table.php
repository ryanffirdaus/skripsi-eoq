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
        Schema::create('pesanan_produk', function (Blueprint $table) {
            $table->id('pesanan_produk_id');
            $table->string('pesanan_id');
            $table->string('produk_id');
            $table->integer('jumlah_produk');
            $table->decimal('harga_satuan', 25, 2);
            $table->decimal('subtotal', 25, 2);
            $table->timestamps();

            $table->foreign('pesanan_id')->references('pesanan_id')->on('pesanan')->onDelete('cascade');
            $table->foreign('produk_id')->references('produk_id')->on('produk')->onDelete('cascade');

            $table->unique(['pesanan_id', 'produk_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan_produk');
    }
};
