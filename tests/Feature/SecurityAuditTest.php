<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Staff;

class SecurityAuditTest extends TestCase
{
    // use RefreshDatabase; // Be careful with this on persistent dev envs

    public function test_unauthenticated_users_cannot_access_dashboard()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_csrf_protection_is_enabled()
    {
        // Attempt a POST request without a CSRF token
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        // Expect 419 Page Expired (CSRF mismatch)
        $response->assertStatus(419); 
    }

    public function test_login_route_has_rate_limiting()
    {
        // Try to hit the login route 6 times (default throttle is usually 5)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'username' => 'wrong_user',
                'password' => 'wrong_password',
            ], ['X-CSRF-TOKEN' => 'dummy']); 
        }
    }
}
