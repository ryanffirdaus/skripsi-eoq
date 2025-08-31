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
            $table->string('pembelian_id')->primary();
            $table->string('pengadaan_id'); // Reference to pengadaan
            $table->string('supplier_id');
            $table->string('nomor_po'); // Purchase Order Number
            $table->date('tanggal_pembelian');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('pajak', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('total_biaya', 15, 2);
            $table->enum('status', ['draft', 'sent', 'confirmed', 'received', 'invoiced', 'paid', 'cancelled'])->default('draft');
            $table->enum('metode_pembayaran', ['cash', 'transfer', 'credit', 'cheque'])->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('catatan')->nullable();
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('pengadaan_id')->references('pengadaan_id')->on('pengadaan')->onDelete('cascade');
            $table->foreign('supplier_id')->references('supplier_id')->on('supplier')->onDelete('restrict');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('restrict');

            // Indexes
            $table->index(['tanggal_pembelian', 'status']);
            $table->index(['supplier_id', 'status']);
            $table->index('nomor_po');
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
