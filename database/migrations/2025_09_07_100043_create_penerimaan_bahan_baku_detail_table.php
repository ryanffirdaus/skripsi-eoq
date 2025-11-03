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
        Schema::create('penerimaan_bahan_baku_detail', function (Blueprint $table) {
            $table->string('penerimaan_detail_id', 11)->primary(); // PND0000001
            $table->string('penerimaan_id', 10);
            $table->string('pembelian_detail_id', 11);
            $table->string('bahan_baku_id', 5);
            $table->integer('qty_diterima');
            $table->integer('qty_diretur')->unsigned()->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('penerimaan_id')->references('penerimaan_id')->on('penerimaan_bahan_baku')->onDelete('cascade');
            $table->foreign('pembelian_detail_id')->references('pembelian_detail_id')->on('pembelian_detail')->onDelete('cascade');
            $table->foreign('bahan_baku_id')->references('bahan_baku_id')->on('bahan_baku')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaan_bahan_baku_detail');
    }
};
