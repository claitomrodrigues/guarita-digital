<?php

namespace Tests\Feature;

use App\Enums\PerfilUsuario;
use App\Enums\StatusAcesso;
use App\Enums\StatusTriagem;
use App\Models\Acesso;
use App\Models\Triagem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriagemTest extends TestCase
{
    use RefreshDatabase;

    public function test_vigilante_pode_autorizar_triagem(): void
    {
        $usuario = User::factory()->create(['perfil' => PerfilUsuario::Seguranca]);
        $acesso = Acesso::factory()->create(['status' => StatusAcesso::NaoCadastrado]);
        $triagem = Triagem::query()->create([
            'acesso_id' => $acesso->id,
            'status' => StatusTriagem::Pendente,
            'iniciada_em' => now(),
        ]);

        $this->actingAs($usuario)->patchJson("/api/triagens/{$triagem->id}/concluir", [
            'decisao' => 'autorizada',
            'nome_visitante' => 'Visitante Teste',
            'documento_visitante' => '123.456.789-00',
            'destino' => 'Direção Geral',
            'motivo_visita' => 'Reunião',
        ])->assertOk()->assertJsonPath('data.status', 'autorizada');

        $this->assertSame('123.456.789-00', $triagem->refresh()->documento_visitante);

        $this->assertDatabaseHas('acessos', [
            'id' => $acesso->id,
            'status' => StatusAcesso::LiberadoManualmente->value,
            'user_id' => $usuario->id,
        ]);
    }
}
