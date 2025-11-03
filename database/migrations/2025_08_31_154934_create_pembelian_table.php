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
        Schema::create('pembelian', function (Blueprint $table) {
            $table->string('pembelian_id', 15)->primary(); // PO-2511-0001

            // Foreign key ke tabel pengadaan (permintaan internal)
            $table->string('pengadaan_id', 10)->index();
            $table->foreign('pengadaan_id')->references('pengadaan_id')->on('pengadaan')->onDelete('restrict');

            // Foreign key ke tabel pemasok
            $table->string('pemasok_id', 5)->index();
            $table->foreign('pemasok_id')->references('pemasok_id')->on('pemasok')->onDelete('restrict');
            $table->date('tanggal_pembelian');
            $table->date('tanggal_kirim_diharapkan')->nullable();
            $table->decimal('total_biaya', 15, 2)->default(0);
            $table->string('metode_pembayaran', 15)->default('tunai')->comment('tunai, transfer, termin');
            $table->string('termin_pembayaran', 50)->nullable()->comment('contoh: 30% DP, 70% saat kirim');
            $table->decimal('jumlah_dp', 15, 2)->default(0)->comment('Jumlah uang muka / down payment');
            $table->string('status', 25)->default('draft')->comment('draft, sent, confirmed, partially_received, fully_received, cancelled');
            $table->text('catatan')->nullable();

            // Foreign keys untuk tracking user
            $table->string('created_by', 6)->nullable();
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->string('updated_by', 6)->nullable();
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
            $table->string('deleted_by', 6)->nullable();
            $table->foreign('deleted_by')->references('user_id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian');
    }
};
