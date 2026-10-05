<?php

namespace Tests\Feature;

use App\Enums\PerfilUsuario;
use App\Enums\StatusAcesso;
use App\Enums\TipoAcesso;
use App\Models\Pessoa;
use App\Models\User;
use App\Models\Veiculo;
use App\Services\AcessoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcessoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_entrada_autorizada_e_alterna_para_saida(): void
    {
        $usuario = User::factory()->create(['perfil' => PerfilUsuario::Seguranca]);
        $pessoa = Pessoa::query()->create([
            'nome' => 'Pessoa Teste',
            'tipo_vinculo' => 'servidor',
            'ativo' => true,
        ]);

        Veiculo::query()->create([
            'pessoa_id' => $pessoa->id,
            'placa' => 'FJB4E12',
            'tipo' => 'carro',
            'ativo' => true,
            'autorizado' => true,
        ]);

        config()->set('guarita.duplicate_window_seconds', 0);
        $service = app(AcessoService::class);

        $entrada = $service->registrarReconhecimento('FJB4E12', $usuario)->acesso;
        $saida = $service->registrarReconhecimento('FJB4E12', $usuario)->acesso;

        $this->assertSame(TipoAcesso::Entrada, $entrada->tipo);
        $this->assertSame(TipoAcesso::Saida, $saida->tipo);
        $this->assertSame(StatusAcesso::Autorizado, $entrada->status);
    }

    public function test_placa_nao_cadastrada_fica_pendente(): void
    {
        $usuario = User::factory()->create(['perfil' => PerfilUsuario::Seguranca]);
        config()->set('guarita.duplicate_window_seconds', 0);

        $acesso = app(AcessoService::class)
            ->registrarReconhecimento('ABC1D23', $usuario)
            ->acesso;

        $this->assertSame(StatusAcesso::NaoCadastrado, $acesso->status);
        $this->assertNull($acesso->veiculo_id);
    }

    public function test_ignora_leitura_duplicada_dentro_da_janela(): void
    {
        $usuario = User::factory()->create(['perfil' => PerfilUsuario::Seguranca]);
        config()->set('guarita.duplicate_window_seconds', 30);

        $primeiro = app(AcessoService::class)->registrarReconhecimento('ABC1D23', $usuario);
        $segundo = app(AcessoService::class)->registrarReconhecimento('ABC1D23', $usuario);

        $this->assertFalse($primeiro->duplicado);
        $this->assertTrue($segundo->duplicado);
        $this->assertSame($primeiro->acesso->id, $segundo->acesso->id);
        $this->assertDatabaseCount('acessos', 1);
    }

    public function test_baixa_confianca_nunca_autoriza_automaticamente(): void
    {
        $usuario = User::factory()->create(['perfil' => PerfilUsuario::Seguranca]);
        $pessoa = Pessoa::query()->create(['nome' => 'Pessoa Teste', 'tipo_vinculo' => 'servidor', 'ativo' => true]);
        Veiculo::query()->create([
            'pessoa_id' => $pessoa->id,
            'placa' => 'ABC1D23',
            'tipo' => 'carro',
            'ativo' => true,
            'autorizado' => true,
        ]);

        $acesso = app(AcessoService::class)->registrarReconhecimento(
            placa: 'ABC1D23',
            usuario: $usuario,
            confianca: 0.20,
        )->acesso;

        $this->assertSame(StatusAcesso::LeituraInconclusiva, $acesso->status);
        $this->assertFalse($acesso->permitePassagem());
    }
}
