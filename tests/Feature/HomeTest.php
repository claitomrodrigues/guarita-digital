<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Pessoa;
use App\Models\Veiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_webcam_capture(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/home')
            ->assertOk()
            ->assertSee('Entradas hoje')
            ->assertSee('Últimas movimentações')
            ->assertSee('camera-video')
            ->assertSee('Tirar foto e reconhecer');
    }

    public function test_home_returns_current_dashboard_data_as_json(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->getJson('/home')
            ->assertOk()
            ->assertJsonStructure([
                'totalVeiculos',
                'veiculosAutorizados',
                'totalEntradasHoje',
                'totalSaidasHoje',
                'veiculosNoPatio',
                'veiculosNoPatioLista',
                'movimentacoesPorHora',
                'movimentacoesRecentes',
                'picoEntrada',
                'picoSaida',
            ]);
    }


    public function test_dashboard_lists_entered_plates_until_their_exit_is_registered(): void
    {
        $usuario = User::factory()->seguranca()->create();

        $this->actingAs($usuario)
            ->postJson(route('acessos.registrar-manual'), [
                'placa' => 'ABC1D23',
                'tipo' => 'entrada',
            ])
            ->assertCreated();

        $this->getJson(route('home'))
            ->assertOk()
            ->assertJsonPath('veiculosNoPatio', 1)
            ->assertJsonPath('veiculosNoPatioLista.0.placa', 'ABC1D23');

        $this->postJson(route('acessos.registrar-manual'), [
            'placa' => 'ABC1D23',
            'tipo' => 'saida',
        ])->assertCreated();

        $this->getJson(route('home'))
            ->assertOk()
            ->assertJsonPath('veiculosNoPatio', 0)
            ->assertJsonPath('veiculosNoPatioLista', []);
    }
    public function test_unregistered_vehicle_can_be_triaged_from_the_dashboard(): void
    {
        $usuario = User::factory()->seguranca()->create();

        $acesso = $this->actingAs($usuario)
            ->postJson(route('acessos.registrar-manual'), [
                'placa' => 'ABC1D23',
                'tipo' => 'entrada',
                'liberar_manualmente' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'nao_cadastrado')
            ->json();

        $this->assertNotNull($acesso['triagem_id']);

        $this->putJson(route('triagens.concluir', $acesso['triagem_id']), [
            'decisao' => 'autorizada',
            'nome_visitante' => 'Visitante Teste',
            'destino' => 'Recepção',
            'motivo_visita' => 'Reunião',
        ])->assertOk()->assertJsonPath('data.status', 'autorizada');

        $this->assertDatabaseHas('acessos', [
            'placa_reconhecida' => 'ABC1D23',
            'status' => 'liberado_manualmente',
        ]);
    }

    public function test_unregistered_vehicle_cannot_be_manually_released(): void
    {
        $usuario = User::factory()->seguranca()->create();

        $this->actingAs($usuario)
            ->postJson(route('acessos.registrar-manual'), [
                'placa' => 'ABC1D23',
                'tipo' => 'entrada',
                'liberar_manualmente' => true,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('acessos', 0);
    }

    public function test_unregistered_vehicle_can_exit_without_authorization_or_triage(): void
    {
        $usuario = User::factory()->seguranca()->create();

        $this->actingAs($usuario)
            ->postJson(route('acessos.registrar-manual'), [
                'placa' => 'ABC1D23',
                'tipo' => 'saida',
                'liberar_manualmente' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'autorizado')
            ->assertJsonPath('triagem_id', null);

        $this->assertDatabaseCount('triagens', 0);
    }

    public function test_registered_vehicle_can_be_manually_released(): void
    {
        $usuario = User::factory()->seguranca()->create();
        $pessoa = Pessoa::query()->create([
            'nome' => 'Pessoa Teste',
            'tipo_vinculo' => 'servidor',
            'ativo' => true,
        ]);
        Veiculo::query()->create([
            'pessoa_id' => $pessoa->id,
            'placa' => 'ABC1D23',
            'tipo' => 'carro',
            'ativo' => true,
            'autorizado' => false,
        ]);

        $this->actingAs($usuario)
            ->postJson(route('acessos.registrar-manual'), [
                'placa' => 'ABC1D23',
                'tipo' => 'entrada',
                'liberar_manualmente' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'liberado_manualmente');
    }

    public function test_captured_image_is_sent_to_python_and_returns_plate_string(): void
    {
        Process::fake([
            '*' => Process::result(output: '{"placa":"ABC1D23"}'),
        ]);

        $usuario = User::factory()->create();
        $pngUmPixel = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nMsAAAAASUVORK5CYII=';

        $this->actingAs($usuario)
            ->postJson(route('camera.reconhecer'), [
                'image' => 'data:image/png;base64,'.$pngUmPixel,
            ])
            ->assertOk()
            ->assertJsonPath('placa', 'ABC1D23')
            ->assertJsonPath('cadastrado', false);
    }

    public function test_invalid_windows_output_is_returned_as_valid_json(): void
    {
        Process::fake([
            '*' => Process::result(errorOutput: "\x93Falha no Python\x94", exitCode: 1),
        ]);

        $usuario = User::factory()->create();
        $pngUmPixel = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nMsAAAAASUVORK5CYII=';

        $resposta = $this->actingAs($usuario)
            ->postJson(route('camera.reconhecer'), [
                'image' => 'data:image/png;base64,'.$pngUmPixel,
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);

        $this->assertTrue(mb_check_encoding($resposta->json('message'), 'UTF-8'));
    }
}
