<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Pesanan;
use App\Models\Pelanggan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesananManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $pelanggan;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::factory()->create(['role_id' => 'RL001', 'nama' => 'Admin']);
        $this->user = User::factory()->create(['role_id' => $role->role_id]);
        $this->pelanggan = Pelanggan::factory()->create();
    }

    public function test_can_view_pesanan_list(): void
    {
        Pesanan::factory(5)->create();

        $response = $this->actingAs($this->user)->get('/pesanan');

        $response->assertStatus(200);
    }

    public function test_can_create_pesanan(): void
    {
        $data = [
            'no_pesanan' => 'PO001',
            'pelanggan_id' => $this->pelanggan->id,
            'tanggal_pesanan' => now(),
            'tanggal_jatuh_tempo' => now()->addDays(7),
            'status' => 'Pending',
            'total' => 500000,
        ];

        $response = $this->actingAs($this->user)->post('/pesanan', $data);

        $this->assertDatabaseHas('pesanans', [
            'no_pesanan' => 'PO001',
            'pelanggan_id' => $this->pelanggan->id,
        ]);
    }

    public function test_can_view_pesanan_detail(): void
    {
        $pesanan = Pesanan::factory()->create();

        $response = $this->actingAs($this->user)->get("/pesanan/{$pesanan->id}");

        $response->assertStatus(200);
    }

    public function test_can_update_pesanan_status(): void
    {
        $pesanan = Pesanan::factory()->create(['status' => 'Pending']);

        $data = ['status' => 'Approved'];

        $response = $this->actingAs($this->user)->put("/pesanan/{$pesanan->id}", $data);

        $this->assertDatabaseHas('pesanans', [
            'id' => $pesanan->id,
            'status' => 'Approved',
        ]);
    }

    public function test_pesanan_number_must_be_unique(): void
    {
        Pesanan::factory()->create(['no_pesanan' => 'PO001']);

        $data = [
            'no_pesanan' => 'PO001',
            'pelanggan_id' => $this->pelanggan->id,
            'status' => 'Pending',
        ];

        $response = $this->actingAs($this->user)->post('/pesanan', $data);

        $response->assertSessionHasErrors('no_pesanan');
    }

    public function test_cannot_create_pesanan_without_pelanggan(): void
    {
        $data = [
            'no_pesanan' => 'PO001',
            'pelanggan_id' => 9999, // Non-existent pelanggan
            'status' => 'Pending',
        ];

        $response = $this->actingAs($this->user)->post('/pesanan', $data);

        $response->assertSessionHasErrors('pelanggan_id');
    }
}
