<?php

namespace Tests\Feature;

use App\Enums\TipoVeiculo;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VeiculoCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_vehicle_for_a_driver(): void
    {
        $usuario = User::factory()->create();
        $condutor = Pessoa::factory()->create();

        $this->actingAs($usuario)
            ->post('/veiculos', [
                'pessoa_id' => $condutor->id,
                'placa' => 'ABC-1D23',
                'marca' => 'Volkswagen',
                'modelo' => 'Gol',
                'cor' => 'Prata',
                'tipo' => TipoVeiculo::Carro->value,
                'ano' => 2020,
                'ativo' => '1',
                'autorizado' => '1',
                'observacoes' => null,
            ])
            ->assertRedirect('/veiculos');

        $this->assertDatabaseHas('veiculos', [
            'pessoa_id' => $condutor->id,
            'placa' => 'ABC1D23',
            'modelo' => 'Gol',
            'autorizado' => true,
        ]);
    }
}
