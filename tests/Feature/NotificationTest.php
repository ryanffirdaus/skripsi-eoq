<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $roleIds = [
        'RL001' => 'Admin',
        'RL002' => 'Staf Gudang',
        'RL003' => 'Staf RnD',
        'RL004' => 'Staf Pengadaan',
        'RL005' => 'Staf Penjualan',
        'RL006' => 'Staf Keuangan',
        'RL007' => 'Manajer Gudang',
        'RL008' => 'Manajer RnD',
        'RL009' => 'Manajer Pengadaan',
        'RL010' => 'Manajer Keuangan',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->roleIds as $role_id => $nama) {
            Role::factory()->create([
                'role_id' => $role_id,
                'nama' => $nama,
            ]);
        }
    }

    public function test_all_authenticated_users_can_access_notifications(): void
    {
        foreach ($this->roleIds as $role_id => $nama) {
            $user = User::factory()->create([
                'role_id' => $role_id,
            ]);

            $response = $this->actingAs($user)->get('/notifications');

            $this->assertTrue(
                $response->isSuccessful(),
                "Role {$nama} should be able to access notifications endpoint"
            );
        }
    }

    public function test_admin_can_access_notifications(): void
    {
        $admin = User::factory()->create([
            'role_id' => 'RL001',
        ]);

        $response = $this->actingAs($admin)->get('/notifications');

        $response->assertStatus(200);
    }

    public function test_staf_gudang_can_access_notifications(): void
    {
        $user = User::factory()->create([
            'role_id' => 'RL002',
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
    }

    public function test_staf_rnd_can_access_notifications(): void
    {
        $user = User::factory()->create([
            'role_id' => 'RL003',
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
    }

    public function test_staf_pengadaan_can_access_notifications(): void
    {
        $user = User::factory()->create([
            'role_id' => 'RL004',
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
    }

    public function test_staf_penjualan_can_access_notifications(): void
    {
        $user = User::factory()->create([
            'role_id' => 'RL005',
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
    }

    public function test_staf_keuangan_can_access_notifications(): void
    {
        $user = User::factory()->create([
            'role_id' => 'RL006',
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
    }

    public function test_manajer_gudang_can_access_notifications(): void
    {
        $user = User::factory()->create([
            'role_id' => 'RL007',
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
    }

    public function test_manajer_rnd_can_access_notifications(): void
    {
        $user = User::factory()->create([
            'role_id' => 'RL008',
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
    }

    public function test_manajer_pengadaan_can_access_notifications(): void
    {
        $user = User::factory()->create([
            'role_id' => 'RL009',
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
    }

    public function test_manajer_keuangan_can_access_notifications(): void
    {
        $user = User::factory()->create([
            'role_id' => 'RL010',
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $response = $this->get('/notifications');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
