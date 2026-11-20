<?php

namespace Tests\Feature;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Database\Seeders\UsuariosSistemaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
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

    public function test_seeder_keeps_only_the_guard_account_active(): void
    {
        $email = mb_strtolower(trim((string) env('ADMIN_EMAIL', 'admin@guarita.local')), 'UTF-8');
        $extraEmail = $email === 'extra-operator@tests.local'
            ? 'another-operator@tests.local'
            : 'extra-operator@tests.local';

        User::factory()->create(['email' => $email]);
        $extra = User::factory()->create(['email' => $extraEmail]);

        app(UsuariosSistemaSeeder::class)->run();

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => env('GUARD_NAME', 'Guarda da Guarita'),
            'perfil' => PerfilUsuario::Seguranca->value,
            'ativo' => true,
            'deleted_at' => null,
        ]);
        $this->assertSoftDeleted('users', ['id' => $extra->id]);
        $this->assertSame(1, User::query()->where('ativo', true)->count());
    }

    public function test_seeder_refuses_a_short_password_in_production(): void
    {
        $this->app['env'] = 'production';
        config()->set('guarita.admin.password', 'short');

        $this->expectException(InvalidArgumentException::class);

        app(UsuariosSistemaSeeder::class)->run();
    }
}
