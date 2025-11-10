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
        Schema::create('pembelian_detail', function (Blueprint $table) {
            $table->string('pembelian_detail_id', 8)->primary(); // PBD0000001

            // Foreign key ke header pembelian
            $table->string('pembelian_id', 15)->index();
            $table->foreign('pembelian_id')->references('pembelian_id')->on('pembelian')->onDelete('cascade');

            // Foreign key ke detail pengadaan untuk traceability
            $table->string('pengadaan_detail_id', 8)->index();
            $table->foreign('pengadaan_detail_id')->references('pengadaan_detail_id')->on('pengadaan_detail')->onDelete('restrict');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian_detail');
    }
};
