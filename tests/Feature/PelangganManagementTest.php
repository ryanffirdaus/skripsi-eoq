<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Pelanggan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PelangganManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::factory()->create(['role_id' => 'RL001', 'nama' => 'Admin']);
        $this->user = User::factory()->create(['role_id' => $role->role_id]);
    }

    public function test_can_view_pelanggan_list(): void
    {
        Pelanggan::factory(5)->create();

        $response = $this->actingAs($this->user)->get('/pelanggan');

        $response->assertStatus(200);
    }

    public function test_can_create_pelanggan(): void
    {
        $data = [
            'kode_pelanggan' => 'PL001',
            'nama_pelanggan' => 'Pelanggan Test',
            'alamat' => 'Jl. Test',
            'kota' => 'Jakarta',
            'provinsi' => 'DKI Jakarta',
            'telepon' => '081234567890',
            'email' => 'test@example.com',
        ];

        $response = $this->actingAs($this->user)->post('/pelanggan', $data);

        $this->assertDatabaseHas('pelanggans', [
            'kode_pelanggan' => 'PL001',
            'nama_pelanggan' => 'Pelanggan Test',
        ]);
    }

    public function test_can_update_pelanggan(): void
    {
        $pelanggan = Pelanggan::factory()->create();

        $data = [
            'nama_pelanggan' => 'Updated Name',
            'alamat' => 'Updated Address',
        ];

        $response = $this->actingAs($this->user)->put("/pelanggan/{$pelanggan->id}", $data);

        $this->assertDatabaseHas('pelanggans', [
            'id' => $pelanggan->id,
            'nama_pelanggan' => 'Updated Name',
            'alamat' => 'Updated Address',
        ]);
    }

    public function test_can_delete_pelanggan(): void
    {
        $pelanggan = Pelanggan::factory()->create();

        $response = $this->actingAs($this->user)->delete("/pelanggan/{$pelanggan->id}");

        $this->assertDatabaseMissing('pelanggans', ['id' => $pelanggan->id]);
    }

    public function test_pelanggan_code_must_be_unique(): void
    {
        Pelanggan::factory()->create(['kode_pelanggan' => 'PL001']);

        $data = [
            'kode_pelanggan' => 'PL001',
            'nama_pelanggan' => 'Duplicate',
        ];

        $response = $this->actingAs($this->user)->post('/pelanggan', $data);

        $response->assertSessionHasErrors('kode_pelanggan');
    }

    public function test_cannot_create_pelanggan_with_invalid_email(): void
    {
        $data = [
            'kode_pelanggan' => 'PL001',
            'nama_pelanggan' => 'Test',
            'email' => 'invalid-email',
        ];

        $response = $this->actingAs($this->user)->post('/pelanggan', $data);

        $response->assertSessionHasErrors('email');
    }
}
