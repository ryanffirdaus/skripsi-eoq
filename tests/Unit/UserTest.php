<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->role_id]);

        $this->assertDatabaseHas('users', [
            'email' => $user->email,
        ]);
    }

    public function test_user_belongs_to_role(): void
    {
        $role = Role::factory()->create(['role_id' => 'RL001', 'nama' => 'Admin']);
        $user = User::factory()->create(['role_id' => $role->role_id]);

        $this->assertEquals($role->role_id, $user->role_id);
        $this->assertNotNull($user->role);
    }

    public function test_user_password_is_hashed(): void
    {
        $password = 'password123';
        $user = User::factory()->create([
            'password' => bcrypt($password),
        ]);

        $this->assertTrue(password_verify($password, $user->password));
    }

    public function test_user_email_must_be_unique(): void
    {
        $user1 = User::factory()->create(['email' => 'test@example.com']);

        // Attempting to create another user with same email should violate unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => 'test@example.com']);
    }

    public function test_user_factory_creates_with_correct_attributes(): void
    {
        $user = User::factory()->make();

        $this->assertNotNull($user->nama_lengkap);
        $this->assertNotNull($user->email);
        $this->assertNotNull($user->password);
    }
}
