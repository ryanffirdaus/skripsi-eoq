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
            $table->string('pembelian_id', 10)->primary();

            // Foreign key ke tabel pengadaan (permintaan internal)
            $table->string('pengadaan_id', 10)->index();
            $table->foreign('pengadaan_id')->references('pengadaan_id')->on('pengadaan')->onDelete('restrict');

            // Foreign key ke tabel supplier
            $table->string('supplier_id', 10)->index();
            $table->foreign('supplier_id')->references('supplier_id')->on('supplier')->onDelete('restrict');

            $table->string('nomor_po', 20)->unique()->comment('Nomor Purchase Order yang formal, cth: PO-202309-0001');
            $table->date('tanggal_pembelian');
            $table->date('tanggal_kirim_diharapkan')->nullable();
            $table->decimal('total_biaya', 15, 2)->default(0);
            $table->string('status', 30)->default('draft')->comment('draft, sent, confirmed, partially_received, fully_received, cancelled');
            $table->text('catatan')->nullable();

            // Foreign keys untuk tracking user
            $table->string('created_by', 10)->nullable();
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->string('updated_by', 10)->nullable();
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
            $table->string('deleted_by', 10)->nullable();
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
