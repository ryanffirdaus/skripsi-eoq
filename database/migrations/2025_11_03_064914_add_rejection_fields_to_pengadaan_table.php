<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add rejection reason fields to support rejection workflow
     */
    public function up(): void
    {
        Schema::table('pengadaan', function (Blueprint $table) {
            // Add rejection reason field
            $table->text('alasan_penolakan')->nullable()->after('catatan');

            // Add rejected_by user tracking
            $table->string('rejected_by')->nullable()->after('alasan_penolakan');
            $table->foreign('rejected_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengadaan', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['alasan_penolakan', 'rejected_by']);
        });
    }
};
