<?php

namespace Tests\Unit;

use App\Models\BahanBaku;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BahanBakuTest extends TestCase
{
    use RefreshDatabase;

    public function test_bahan_baku_can_be_created(): void
    {
        $bahanBaku = BahanBaku::factory()->create([
            'kode_bahan' => 'BB001',
            'nama_bahan' => 'Test Bahan',
        ]);

        $this->assertDatabaseHas('bahan_bakus', [
            'kode_bahan' => 'BB001',
            'nama_bahan' => 'Test Bahan',
        ]);
    }

    public function test_bahan_baku_has_required_attributes(): void
    {
        $bahanBaku = BahanBaku::factory()->create();

        $this->assertNotNull($bahanBaku->kode_bahan);
        $this->assertNotNull($bahanBaku->nama_bahan);
        $this->assertNotNull($bahanBaku->satuan);
        $this->assertIsNumeric($bahanBaku->stok);
    }

    public function test_bahan_baku_can_be_updated(): void
    {
        $bahanBaku = BahanBaku::factory()->create(['stok' => 100]);

        $bahanBaku->update(['stok' => 200]);

        $this->assertDatabaseHas('bahan_bakus', [
            'id' => $bahanBaku->id,
            'stok' => 200,
        ]);
    }

    public function test_bahan_baku_can_be_deleted(): void
    {
        $bahanBaku = BahanBaku::factory()->create();
        $id = $bahanBaku->id;

        $bahanBaku->delete();

        $this->assertDatabaseMissing('bahan_bakus', ['id' => $id]);
    }

    public function test_bahan_baku_stok_cannot_be_negative(): void
    {
        $this->expectException(\Exception::class);

        $bahanBaku = BahanBaku::factory()->create();
        $bahanBaku->update(['stok' => -10]);
    }
}
