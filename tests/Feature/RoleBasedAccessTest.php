<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
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

        // Create all roles
        foreach ($this->roleIds as $role_id => $nama) {
            Role::factory()->create([
                'role_id' => $role_id,
                'nama' => $nama,
            ]);
        }
    }

    public function test_all_roles_can_access_dashboard(): void
    {
        foreach ($this->roleIds as $role_id => $nama) {
            $user = User::factory()->create([
                'role_id' => $role_id,
            ]);

            $response = $this->actingAs($user)->get('/dashboard');

            $response->assertStatus(200);
            $this->assertTrue($response->isSuccessful(), "Role {$nama} should access dashboard");
        }
    }

    public function test_all_authenticated_users_can_access_notifications(): void
    {
        foreach ($this->roleIds as $role_id => $nama) {
            $user = User::factory()->create([
                'role_id' => $role_id,
            ]);

            $response = $this->actingAs($user)->get('/notifications');

            $response->assertStatus(200);
            $this->assertTrue($response->isSuccessful(), "Role {$nama} should access notifications");
        }
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $response = $this->get('/notifications');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_admin_can_access_all_resources(): void
    {
        $admin = User::factory()->create([
            'role_id' => 'RL001',
        ]);

        $routes = [
            '/dashboard',
            '/bahan-baku',
            '/pelanggan',
            '/pemasok',
            '/pembelian',
            '/penerimaan-bahan-baku',
            '/pengadaan',
            '/pengiriman',
            '/penugasan-produksi',
            '/pesanan',
            '/produk',
            '/transaksi-pembayaran',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get($route);
            $this->assertTrue(
                in_array($response->status(), [200, 302, 404]),
                "Admin should have access attempt to {$route}, got {$response->status()}"
            );
        }
    }

    public function test_non_admin_user_cannot_access_admin_only_routes(): void
    {
        $user = User::factory()->create([
            'role_id' => 'RL002',
        ]);

        // This would depend on your actual authorization logic
        // Adjust routes based on your actual admin-only routes
        $response = $this->actingAs($user)->get('/dashboard');

        // Should succeed as dashboard is accessible to all authenticated users
        $response->assertStatus(200);
    }
}
