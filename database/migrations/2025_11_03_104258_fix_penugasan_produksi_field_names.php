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
        if (Schema::hasTable('penugasan_produksi')) {
            Schema::table('penugasan_produksi', function (Blueprint $table) {
                // Rename field names to Indonesian
                if (Schema::hasColumn('penugasan_produksi', 'created_by')) {
                    $table->renameColumn('created_by', 'dibuat_oleh');
                }
                if (Schema::hasColumn('penugasan_produksi', 'updated_by')) {
                    $table->renameColumn('updated_by', 'diupdate_oleh');
                }
                if (Schema::hasColumn('penugasan_produksi', 'deleted_by')) {
                    $table->renameColumn('deleted_by', 'dihapus_oleh');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('penugasan_produksi')) {
            Schema::table('penugasan_produksi', function (Blueprint $table) {
                // Revert field names to English
                if (Schema::hasColumn('penugasan_produksi', 'dibuat_oleh')) {
                    $table->renameColumn('dibuat_oleh', 'created_by');
                }
                if (Schema::hasColumn('penugasan_produksi', 'diupdate_oleh')) {
                    $table->renameColumn('diupdate_oleh', 'updated_by');
                }
                if (Schema::hasColumn('penugasan_produksi', 'dihapus_oleh')) {
                    $table->renameColumn('dihapus_oleh', 'deleted_by');
                }
            });
        }
    }
};
