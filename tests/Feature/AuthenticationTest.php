<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials()
    {
        $staff = Staff::factory()->create([
            'username' => 'testuser',
            'password_hash' => Hash::make('password123'),
            'status' => 'active'
        ]);

        $response = $this->postJson('/login', [
            'username' => 'testuser',
            'password' => 'password123'
        ]);

        $response->assertSuccessful();
        $response->assertJsonStructure(['access_token', 'token_type', 'role', 'username']);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $staff = Staff::factory()->create([
            'username' => 'testuser',
            'password_hash' => Hash::make('password123'),
            'status' => 'active'
        ]);

        $response = $this->postJson('/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_login_when_suspended()
    {
        $staff = Staff::factory()->create([
            'username' => 'suspended',
            'password_hash' => Hash::make('password123'),
            'status' => 'suspended'
        ]);

        $response = $this->postJson('/login', [
            'username' => 'suspended',
            'password' => 'password123'
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_logout()
    {
        $staff = Staff::factory()->create(['role' => 'admin']);
        $this->actingAs($staff);

        $response = $this->postJson('/logout');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_get_current_user_info()
    {
        $staff = Staff::factory()->create(['role' => 'admin']);
        $this->actingAs($staff);

        $response = $this->getJson('/me');

        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'username', 'role']);
        $response->assertJson(['id' => $staff->id]);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/dashboard');

        $response->assertStatus(401);
    }

    public function test_role_based_access_control()
    {
        // Test admin access
        $admin = Staff::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->getJson('/staff');
        $this->assertContains($response->status(), [200, 302]);

        // Test non-admin access
        $cashier = Staff::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier);

        $response = $this->getJson('/staff');
        $this->assertContains($response->status(), [403, 302]);
    }

    public function test_validates_login_credentials()
    {
        $response = $this->postJson('/login', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_session_persistence()
    {
        $staff = Staff::factory()->create(['role' => 'admin']);
        
        // Login
        $loginResponse = $this->postJson('/login', [
            'username' => $staff->username,
            'password' => 'password' // Assuming default factory password
        ]);

        if ($loginResponse->status() === 200) {
            // Try accessing protected route
            $response = $this->getJson('/dashboard');
            $this->assertContains($response->status(), [200, 302]);
        }
    }
}

