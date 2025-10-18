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
        Schema::create('penugasan_produksi', function (Blueprint $table) {
            $table->id('penugasan_id');
            $table->foreignId('pengadaan_id')->constrained('pengadaan', 'pengadaan_id')->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users', 'user_id')->onDelete('cascade');
            $table->integer('qty_assigned');
            $table->integer('qty_completed')->default(0);
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'cancelled'])->default('assigned');
            $table->date('deadline');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
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
