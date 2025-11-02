<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create new table with string ID
        Schema::create('penugasan_produksi_new', function (Blueprint $table) {
            $table->string('penugasan_id', 50)->primary();
            $table->string('pengadaan_detail_id');
            $table->foreign('pengadaan_detail_id')->references('pengadaan_detail_id')->on('pengadaan_detail')->onDelete('cascade');

            // user_id: siapa yang ditugaskan
            $table->string('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');

            // Jumlah yang harus diproduksi
            $table->integer('jumlah_produksi');

            // Status: ditugaskan, proses, selesai, dibatalkan
            $table->enum('status', ['ditugaskan', 'proses', 'selesai', 'dibatalkan'])->default('ditugaskan');

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

        // Copy data from old table with new ID format
        $oldRecords = DB::table('penugasan_produksi')->get();
        foreach ($oldRecords as $record) {
            $newId = 'PPD' . str_pad($record->penugasan_id, 5, '0', STR_PAD_LEFT);

            DB::table('penugasan_produksi_new')->insert([
                'penugasan_id' => $newId,
                'pengadaan_detail_id' => $record->pengadaan_detail_id,
                'user_id' => $record->user_id,
                'jumlah_produksi' => $record->jumlah_produksi,
                'status' => $record->status,
                'deadline' => $record->deadline,
                'catatan' => $record->catatan,
                'created_by' => $record->created_by,
                'updated_by' => $record->updated_by,
                'deleted_by' => $record->deleted_by,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
                'deleted_at' => $record->deleted_at,
            ]);
        }

        // Drop old table and rename new one
        Schema::drop('penugasan_produksi');
        Schema::rename('penugasan_produksi_new', 'penugasan_produksi');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Create old table with auto-increment ID
        Schema::create('penugasan_produksi_old', function (Blueprint $table) {
            $table->id('penugasan_id');
            $table->string('pengadaan_detail_id');
            $table->foreign('pengadaan_detail_id')->references('pengadaan_detail_id')->on('pengadaan_detail')->onDelete('cascade');

            $table->string('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');

            $table->integer('jumlah_produksi');
            $table->enum('status', ['ditugaskan', 'proses', 'selesai', 'dibatalkan'])->default('ditugaskan');
            $table->date('deadline');
            $table->text('catatan')->nullable();

            $table->string('created_by')->nullable();
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');

            $table->string('updated_by')->nullable();
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');

            $table->string('deleted_by')->nullable();
            $table->foreign('deleted_by')->references('user_id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index('pengadaan_detail_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_by');
        });

        // Copy data back with extracted numeric IDs
        $newRecords = DB::table('penugasan_produksi')->get();
        foreach ($newRecords as $record) {
            $oldId = (int) substr($record->penugasan_id, 3);

            DB::table('penugasan_produksi_old')->insert([
                'penugasan_id' => $oldId,
                'pengadaan_detail_id' => $record->pengadaan_detail_id,
                'user_id' => $record->user_id,
                'jumlah_produksi' => $record->jumlah_produksi,
                'status' => $record->status,
                'deadline' => $record->deadline,
                'catatan' => $record->catatan,
                'created_by' => $record->created_by,
                'updated_by' => $record->updated_by,
                'deleted_by' => $record->deleted_by,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
                'deleted_at' => $record->deleted_at,
            ]);
        }

        Schema::drop('penugasan_produksi');
        Schema::rename('penugasan_produksi_old', 'penugasan_produksi');
    }
};
