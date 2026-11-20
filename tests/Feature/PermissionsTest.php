<?php

namespace Tests\Feature;

use App\Enums\PerfilUsuario;
use App\Models\Pessoa;
use App\Models\User;
use App\Models\Veiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seguranca_pode_manter_condutores_e_veiculos(): void
    {
        $seguranca = User::factory()->create(['perfil' => PerfilUsuario::Seguranca]);

        $this->actingAs($seguranca)
            ->get('/condutores/create')
            ->assertOk();

        $this->actingAs($seguranca)
            ->get('/veiculos/create')
            ->assertOk();

        $this->assertTrue($seguranca->can('create', Pessoa::class));
        $this->assertTrue($seguranca->can('create', Veiculo::class));

        $this->actingAs($seguranca)
            ->post('/condutores', [
                'nome' => 'Condutor da guarita',
                'tipo_vinculo' => 'servidor',
                'ativo' => '1',
            ])
            ->assertRedirect('/condutores');

        $condutor = Pessoa::query()->where('nome', 'Condutor da guarita')->firstOrFail();

        $this->actingAs($seguranca)
            ->post('/veiculos', [
                'pessoa_id' => $condutor->id,
                'placa' => 'ABC1D23',
                'modelo' => 'Veículo teste',
                'tipo' => 'carro',
                'ativo' => '1',
                'autorizado' => '1',
            ])
            ->assertRedirect('/veiculos');

        $this->assertDatabaseHas('veiculos', [
            'pessoa_id' => $condutor->id,
            'placa' => 'ABC1D23',
        ]);
    }
}
