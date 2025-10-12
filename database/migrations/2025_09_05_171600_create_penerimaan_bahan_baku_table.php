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
            $table->string('pembelian_id', 10);
            $table->string('pemasok_id', 10);
            $table->string('nomor_penerimaan', 50)->unique();
            $table->string('nomor_surat_jalan');
            $table->date('tanggal_penerimaan');
            $table->string('status', 20)->default('confirmed');
            $table->text('catatan')->nullable();
            $table->string('created_by', 10)->nullable();
            $table->string('updated_by', 10)->nullable();
            $table->timestamps();

            $table->foreign('pembelian_id')->references('pembelian_id')->on('pembelian')->onDelete('cascade');
            $table->foreign('pemasok_id')->references('pemasok_id')->on('pemasok')->onDelete('restrict');
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
