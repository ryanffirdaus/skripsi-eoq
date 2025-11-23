<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\BahanBaku;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BahanBakuManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::factory()->create(['role_id' => 'RL001', 'nama' => 'Admin']);
        $this->user = User::factory()->create(['role_id' => $role->role_id]);
    }

    public function test_can_view_bahan_baku_list(): void
    {
        BahanBaku::factory(5)->create();

        $response = $this->actingAs($this->user)->get('/bahan-baku');

        $response->assertStatus(200);
    }

    public function test_can_create_bahan_baku(): void
    {
        $data = [
            'kode_bahan' => 'BB001',
            'nama_bahan' => 'Bahan Test',
            'satuan' => 'kg',
            'stok' => 100,
            'harga_beli' => 50000,
        ];

        $response = $this->actingAs($this->user)->post('/bahan-baku', $data);

        $this->assertDatabaseHas('bahan_bakus', [
            'kode_bahan' => 'BB001',
            'nama_bahan' => 'Bahan Test',
        ]);
    }

    public function test_can_update_bahan_baku(): void
    {
        $bahanBaku = BahanBaku::factory()->create();

        $data = [
            'nama_bahan' => 'Updated Name',
            'stok' => 200,
        ];

        $response = $this->actingAs($this->user)->put("/bahan-baku/{$bahanBaku->id}", $data);

        $this->assertDatabaseHas('bahan_bakus', [
            'id' => $bahanBaku->id,
            'nama_bahan' => 'Updated Name',
            'stok' => 200,
        ]);
    }

    public function test_can_delete_bahan_baku(): void
    {
        $bahanBaku = BahanBaku::factory()->create();

        $response = $this->actingAs($this->user)->delete("/bahan-baku/{$bahanBaku->id}");

        $this->assertDatabaseMissing('bahan_bakus', ['id' => $bahanBaku->id]);
    }

    public function test_cannot_create_bahan_baku_with_invalid_data(): void
    {
        $data = [
            'kode_bahan' => '',
            'nama_bahan' => '',
            'stok' => -10, // Invalid: negative stock
        ];

        $response = $this->actingAs($this->user)->post('/bahan-baku', $data);

        $response->assertSessionHasErrors(['kode_bahan', 'nama_bahan']);
    }

    public function test_bahan_baku_code_must_be_unique(): void
    {
        BahanBaku::factory()->create(['kode_bahan' => 'BB001']);

        $data = [
            'kode_bahan' => 'BB001',
            'nama_bahan' => 'Duplicate Code',
            'satuan' => 'kg',
            'stok' => 100,
        ];

        $response = $this->actingAs($this->user)->post('/bahan-baku', $data);

        $response->assertSessionHasErrors('kode_bahan');
    }
}
