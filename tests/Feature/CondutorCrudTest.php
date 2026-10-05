<?php

namespace Tests\Feature;

use App\Enums\TipoVinculo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CondutorCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_driver(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post('/condutores', [
                'nome' => 'Maria da Silva',
                'cpf' => null,
                'matricula' => '20260001',
                'email' => 'maria@example.com',
                'telefone' => '(55) 99999-0000',
                'tipo_vinculo' => TipoVinculo::Aluno->value,
                'ativo' => '1',
                'observacoes' => null,
            ])
            ->assertRedirect('/condutores');

        $this->get('/condutores')
            ->assertOk()
            ->assertSee('Maria da Silva')
            ->assertDontSee('20260001')
            ->assertDontSee('CPF/Matrícula');

        $this->assertDatabaseHas('pessoas', [
            'nome' => 'Maria da Silva',
            'matricula' => '20260001',
            'ativo' => true,
        ]);
    }
}
