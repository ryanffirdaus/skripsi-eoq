<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Pengadaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengadaanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::factory()->create(['role_id' => 'RL001', 'nama' => 'Admin']);
        $this->user = User::factory()->create(['role_id' => $role->role_id]);
    }

    public function test_can_view_pengadaan_list(): void
    {
        Pengadaan::factory(5)->create();

        $response = $this->actingAs($this->user)->get('/pengadaan');

        $response->assertStatus(200);
    }

    public function test_can_create_pengadaan(): void
    {
        $data = [
            'no_po' => 'PUR001',
            'status' => 'Draft',
            'total' => 1000000,
        ];

        $response = $this->actingAs($this->user)->post('/pengadaan', $data);

        $this->assertDatabaseHas('pengadaans', [
            'no_po' => 'PUR001',
        ]);
    }

    public function test_can_update_pengadaan(): void
    {
        $pengadaan = Pengadaan::factory()->create();

        $data = ['status' => 'Approved'];

        $response = $this->actingAs($this->user)->put("/pengadaan/{$pengadaan->id}", $data);

        $this->assertDatabaseHas('pengadaans', [
            'id' => $pengadaan->id,
            'status' => 'Approved',
        ]);
    }

    public function test_can_view_pengadaan_detail(): void
    {
        $pengadaan = Pengadaan::factory()->create();

        $response = $this->actingAs($this->user)->get("/pengadaan/{$pengadaan->id}");

        $response->assertStatus(200);
    }

    public function test_pengadaan_number_must_be_unique(): void
    {
        Pengadaan::factory()->create(['no_po' => 'PUR001']);

        $data = [
            'no_po' => 'PUR001',
            'status' => 'Draft',
        ];

        $response = $this->actingAs($this->user)->post('/pengadaan', $data);

        $response->assertSessionHasErrors('no_po');
    }

    public function test_cannot_create_pengadaan_with_invalid_total(): void
    {
        $data = [
            'no_po' => 'PUR001',
            'status' => 'Draft',
            'total' => -100, // Invalid: negative total
        ];

        $response = $this->actingAs($this->user)->post('/pengadaan', $data);

        $response->assertSessionHasErrors('total');
    }
}
