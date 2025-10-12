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
        Schema::create('penerimaan_bahan_baku', function (Blueprint $table) {
            $table->string('penerimaan_id', 10)->primary();
            $table->string('pembelian_detail_id', 11);
            $table->integer('qty_diterima');
            $table->string('created_by', 10)->nullable();
            $table->string('updated_by', 10)->nullable();
            $table->timestamps();

            $table->foreign('pembelian_detail_id')->references('pembelian_detail_id')->on('pembelian_detail')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaan_bahan_baku');
    }
};
