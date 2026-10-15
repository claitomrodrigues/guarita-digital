<?php

namespace Tests\Feature;

use App\Enums\OrigemAcesso;
use App\Enums\StatusAcesso;
use App\Enums\TipoAcesso;
use App\Models\Acesso;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatorioTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_usuario_pode_ver_relatorio_com_indicadores_filtrados_por_periodo(): void
    {
        Carbon::setTestNow('2026-10-06 12:00:00');
        $usuario = User::factory()->create();
        Acesso::factory()->create([
            'placa_reconhecida' => 'ABC1D23',
            'data_hora' => '2026-10-03 08:00:00',
            'tipo' => TipoAcesso::Entrada,
            'status' => StatusAcesso::Autorizado,
        ]);
        Acesso::factory()->create([
            'placa_reconhecida' => 'DEF4G56',
            'data_hora' => '2026-10-04 17:00:00',
            'tipo' => TipoAcesso::Saida,
            'status' => StatusAcesso::Bloqueado,
            'origem' => OrigemAcesso::Manual,
        ]);
        Acesso::factory()->create([
            'placa_reconhecida' => 'JKL7M89',
            'data_hora' => '2026-09-30 12:00:00',
        ]);

        $this->actingAs($usuario)
            ->get(route('relatorios.index', [
                'data_inicio' => '2026-10-01',
                'data_fim' => '2026-10-06',
            ]))
            ->assertOk()
            ->assertSee('Acessos registrados')
            ->assertSee('ABC1D23')
            ->assertSee('DEF4G56')
            ->assertDontSee('JKL7M89')
            ->assertSee('2 registros')
            ->assertSee('Baixar relatório PDF')
            ->assertSee('Baixar CSV');
    }

    public function test_relatorio_filtra_placa_e_tipo(): void
    {
        Carbon::setTestNow('2026-10-06 12:00:00');
        $usuario = User::factory()->create();
        Acesso::factory()->create([
            'placa_reconhecida' => 'ABC1D23',
            'data_hora' => '2026-10-03 08:00:00',
            'tipo' => TipoAcesso::Entrada,
        ]);
        Acesso::factory()->create([
            'placa_reconhecida' => 'ABC1D23',
            'data_hora' => '2026-10-04 17:00:00',
            'tipo' => TipoAcesso::Saida,
        ]);
        Acesso::factory()->create([
            'placa_reconhecida' => 'XYZ9W87',
            'data_hora' => '2026-10-05 17:00:00',
            'tipo' => TipoAcesso::Saida,
        ]);

        $this->actingAs($usuario)
            ->get(route('relatorios.index', [
                'data_inicio' => '2026-10-01',
                'data_fim' => '2026-10-06',
                'placa' => 'ABC1D23',
                'tipo' => TipoAcesso::Entrada->value,
            ]))
            ->assertOk()
            ->assertSee('ABC1D23')
            ->assertSee('1 registros')
            ->assertDontSee('XYZ9W87');
    }

    public function test_usuario_pode_baixar_relatorio_pdf(): void
    {
        Carbon::setTestNow('2026-10-06 12:00:00');
        $usuario = User::factory()->create();
        Acesso::factory()->create([
            'placa_reconhecida' => 'ABC1D23',
            'data_hora' => '2026-10-03 08:00:00',
        ]);

        $resposta = $this->actingAs($usuario)
            ->get(route('relatorios.pdf', [
                'data_inicio' => '2026-10-01',
                'data_fim' => '2026-10-06',
            ]));

        $resposta->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('relatorio-acessos-2026-10-01-2026-10-06.pdf');

        $this->assertStringStartsWith('%PDF-', $resposta->getContent());
    }

    public function test_visitante_nao_autenticado_nao_pode_consultar_relatorios(): void
    {
        $this->get(route('relatorios.index'))->assertRedirect(route('login'));
    }
}
