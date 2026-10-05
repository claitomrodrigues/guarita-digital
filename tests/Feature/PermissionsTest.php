<?php

namespace Tests\Feature;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seguranca_pode_consultar_mas_nao_alterar_cadastros(): void
    {
        $seguranca = User::factory()->create(['perfil' => PerfilUsuario::Seguranca]);

        $this->actingAs($seguranca)
            ->getJson('/api/pessoas')
            ->assertOk();

        $this->actingAs($seguranca)
            ->postJson('/api/pessoas', [
                'nome' => 'Pessoa Teste',
                'tipo_vinculo' => 'servidor',
            ])
            ->assertForbidden();
    }

    public function test_administrador_pode_criar_pessoa(): void
    {
        $administrador = User::factory()->create(['perfil' => PerfilUsuario::Administrador]);

        $this->actingAs($administrador)
            ->postJson('/api/pessoas', [
                'nome' => 'Pessoa Teste',
                'tipo_vinculo' => 'servidor',
            ])
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Pessoa Teste');
    }

    public function test_administrador_pode_criar_mais_de_um_vigilante(): void
    {
        $administrador = User::factory()->create(['perfil' => PerfilUsuario::Administrador]);

        foreach ([1, 2] as $numero) {
            $this->actingAs($administrador)->postJson('/api/usuarios', [
                'name' => "Vigilante {$numero}",
                'email' => "vigilante{$numero}@teste.local",
                'password' => 'SenhaForte123',
                'password_confirmation' => 'SenhaForte123',
                'perfil' => PerfilUsuario::Seguranca->value,
            ])->assertCreated();
        }

        $this->assertDatabaseCount('users', 3);
    }
}
