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
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->string('pelanggan_id', 6)->primary(); // PL001
            $table->string('email_pelanggan', 50)->unique();
            $table->string('nama_pelanggan', 50);
            $table->string('nomor_telepon', 20);
            $table->text('alamat_pembayaran');
            $table->text('alamat_pengiriman');
            $table->string('created_by', 6)->nullable();
            $table->string('updated_by', 6)->nullable();
            $table->string('deleted_by', 6)->nullable();
            $table->softDeletes();
            $table->timestamps();

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
        Schema::dropIfExists('pelanggan');
    }
};
