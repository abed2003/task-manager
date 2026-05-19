<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_screen_is_disabled(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_public_registration_post_is_disabled(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertNotFound();
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_admin_can_create_users(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Created User',
            'email' => 'created@example.com',
            'role' => 'user',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('admin.users'));
        $this->assertDatabaseHas('users', [
            'name' => 'Created User',
            'email' => 'created@example.com',
            'role' => 'user',
        ]);
    }

    public function test_admin_can_update_users(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'role' => 'admin',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('admin.users'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_delete_other_users(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users'));
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_remove_admin_role_from_their_own_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'user',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);
    }
}
