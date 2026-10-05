<?php

namespace Tests\Feature;

use App\Enums\SentidoPontoAcesso;
use App\Models\PontoAcesso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CameraApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_camera_autenticada_registra_e_nao_duplica_captura(): void
    {
        $token = 'gd_'.str_repeat('a', 64);
        $ponto = PontoAcesso::factory()->create([
            'sentido' => SentidoPontoAcesso::Entrada,
            'ativo' => true,
            'camera_token_hash' => hash('sha256', $token),
        ]);
        $payload = [
            'capture_id' => 'captura-camera-0001',
            'placa' => 'ABC1D23',
            'confianca_ocr' => 0.93,
            'confianca_yolo' => 0.88,
            'modelo_placa' => 'mercosul',
            'quadros_confirmados' => 3,
            'capturado_em' => now()->toIso8601String(),
        ];

        $this->withToken($token)->postJson('/api/v1/camera/reconhecimentos', $payload)
            ->assertCreated()
            ->assertJsonPath('decisao', 'triagem');

        $this->withToken($token)->postJson('/api/v1/camera/reconhecimentos', $payload)
            ->assertOk()
            ->assertJsonPath('duplicado', true);

        $this->assertDatabaseCount('acessos', 1);
        $this->assertDatabaseCount('triagens', 1);
        $this->assertDatabaseHas('acessos', ['ponto_acesso_id' => $ponto->id]);
    }

    public function test_camera_sem_token_e_rejeitada(): void
    {
        $this->postJson('/api/v1/camera/reconhecimentos', [])->assertUnauthorized();
    }
}
