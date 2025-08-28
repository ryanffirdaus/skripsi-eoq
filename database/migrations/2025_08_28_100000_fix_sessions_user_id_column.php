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
        // Create a migration to fix any issues with the sessions table
        Schema::table('sessions', function (Blueprint $table) {
            // First drop the index if it exists
            if (Schema::hasColumn('sessions', 'user_id')) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            }

            // Add the column back with correct type
            $table->string('user_id', 10)->nullable()->index()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Just in case we need to revert
        Schema::table('sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sessions', 'user_id')) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            }

            $table->string('user_id', 10)->nullable()->index()->after('id');
        });
    }
};
