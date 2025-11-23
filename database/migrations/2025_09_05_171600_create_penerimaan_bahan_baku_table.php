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
            $table->string('penerimaan_id', 6)->primary(); // PN0000001
            $table->string('pembelian_detail_id', 8);
            $table->integer('qty_diterima');
            $table->string('dibuat_oleh', 6)->nullable();
            $table->string('diubah_oleh', 6)->nullable();
            $table->string('dihapus_oleh', 6)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pembelian_detail_id')->references('pembelian_detail_id')->on('pembelian_detail')->onDelete('cascade');
            $table->foreign('dibuat_oleh')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('diubah_oleh')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('dihapus_oleh')->references('user_id')->on('users')->onDelete('set null');
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
