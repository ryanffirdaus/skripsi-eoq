<?php

namespace Tests\Unit;

use App\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_can_be_created(): void
    {
        $role = Role::factory()->create([
            'role_id' => 'RL001',
            'nama' => 'Admin',
        ]);

        $this->assertDatabaseHas('roles', [
            'role_id' => 'RL001',
            'nama' => 'Admin',
        ]);
    }

    public function test_all_required_roles_exist(): void
    {
        $roleIds = ['RL001', 'RL002', 'RL003', 'RL004', 'RL005', 'RL006', 'RL007', 'RL008', 'RL009', 'RL010'];
        $names = [
            'Admin',
            'Staf Gudang',
            'Staf RnD',
            'Staf Pengadaan',
            'Staf Penjualan',
            'Staf Keuangan',
            'Manajer Gudang',
            'Manajer RnD',
            'Manajer Pengadaan',
            'Manajer Keuangan'
        ];

        foreach ($roleIds as $index => $role_id) {
            $role = Role::factory()->create([
                'role_id' => $role_id,
                'nama' => $names[$index],
            ]);

            $this->assertDatabaseHas('roles', [
                'role_id' => $role_id,
                'nama' => $names[$index],
            ]);
        }
    }

    public function test_role_factory_creates_with_correct_attributes(): void
    {
        $role = Role::factory()->make();

        $this->assertNotNull($role->role_id);
        $this->assertNotNull($role->nama);
    }
}
