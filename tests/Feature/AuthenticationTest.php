<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/home')->assertRedirect('/login');
    }

    public function test_active_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'admin@guarita.local',
            'password' => 'Guarita@2026',
        ]);

        $this->post('/login', [
            'login' => 'admin@guarita.local',
            'password' => 'Guarita@2026',
        ])->assertRedirect('/home');

        $this->assertAuthenticated();
    }
}
