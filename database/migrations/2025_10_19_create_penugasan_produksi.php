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
        Schema::dropIfExists('penugasan_produksi');

        Schema::create('penugasan_produksi', function (Blueprint $table) {
            $table->id('penugasan_id');
            $table->string('pengadaan_detail_id');
            $table->foreign('pengadaan_detail_id')->references('pengadaan_detail_id')->on('pengadaan_detail')->onDelete('cascade');

            // user_id: siapa yang ditugaskan
            $table->string('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');

            // Jumlah yang harus diproduksi
            $table->integer('jumlah_produksi');

            // Status: assigned, in_progress, completed, cancelled
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'cancelled'])->default('assigned');

            $table->date('deadline');
            $table->text('catatan')->nullable();

            // created_by: siapa yang menugaskan
            $table->string('created_by')->nullable();
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');

            // updated_by: siapa yang terakhir update
            $table->string('updated_by')->nullable();
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');

            // deleted_by: siapa yang menghapus
            $table->string('deleted_by')->nullable();
            $table->foreign('deleted_by')->references('user_id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('pengadaan_detail_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penugasan_produksi');
    }
};
