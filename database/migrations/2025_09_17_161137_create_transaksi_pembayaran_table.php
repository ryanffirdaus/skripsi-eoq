<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_pembayaran', function (Blueprint $table) {
            $table->string('transaksi_pembayaran_id', 50)->primary();
            $table->string('pembelian_id', 50);
            $table->foreign('pembelian_id')->references('pembelian_id')->on('pembelian');
            $table->enum('jenis_pembayaran', ['dp', 'termin', 'pelunasan'])->default('pelunasan');
            $table->dateTime('tanggal_pembayaran');
            $table->decimal('total_pembayaran', 15, 2);
            $table->string('bukti_pembayaran', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->string('deleted_by', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_pembayarans');
    }
};
