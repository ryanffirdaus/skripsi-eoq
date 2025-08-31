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
        Schema::create('pengadaan', function (Blueprint $table) {
            $table->string('pengadaan_id')->primary();
            $table->string('supplier_id');
            $table->enum('jenis_pengadaan', ['pesanan', 'rop', 'manual']); // Trigger: order-based, ROP-based, or manual
            $table->string('pesanan_id')->nullable(); // Reference to pesanan if triggered by order
            $table->date('tanggal_pengadaan');
            $table->date('tanggal_dibutuhkan');
            $table->date('tanggal_delivery')->nullable();
            $table->decimal('total_biaya', 15, 2)->default(0);
            $table->enum('status', ['draft', 'pending', 'approved', 'ordered', 'partial_received', 'received', 'cancelled'])
                ->default('draft');
            $table->enum('prioritas', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->text('catatan')->nullable();
            $table->text('alasan_pengadaan')->nullable(); // Why this procurement is needed
            $table->string('nomor_po')->nullable(); // Purchase Order number
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('supplier_id')->references('supplier_id')->on('supplier')->onDelete('cascade');
            $table->foreign('pesanan_id')->references('pesanan_id')->on('pesanan')->onDelete('set null');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengadaan');
    }
};
