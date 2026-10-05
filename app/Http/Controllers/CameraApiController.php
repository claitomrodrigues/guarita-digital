<?php

namespace App\Http\Controllers;

use App\Enums\TipoAcesso;
use App\Http\Requests\ReceberReconhecimentoCameraRequest;
use App\Http\Resources\AcessoResource;
use App\Models\PontoAcesso;
use App\Services\AcessoService;
use App\Services\TriagemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CameraApiController extends Controller
{
    public function reconhecer(
        ReceberReconhecimentoCameraRequest $request,
        AcessoService $acessos,
        TriagemService $triagens,
    ): JsonResponse {
        $dados = $request->validated();
        /** @var PontoAcesso $ponto */
        $ponto = $request->attributes->get('camera_ponto_acesso');
        $caminho = null;

        if ($request->hasFile('imagem')) {
            $arquivo = $request->file('imagem');
            $caminho = $arquivo->storeAs(
                'capturas/'.now()->format('Y/m/d'),
                Str::uuid().'.'.($arquivo->guessExtension() ?: 'jpg'),
                (string) config('guarita.capturas_disk', 'local'),
            );
        }

        try {
            $resultado = $acessos->registrarReconhecimento(
                placa: $dados['placa'],
                pontoAcesso: $ponto,
                tipoSolicitado: isset($dados['tipo']) ? TipoAcesso::from($dados['tipo']) : null,
                imagem: is_string($caminho) ? $caminho : null,
                confianca: (float) $dados['confianca_ocr'],
                confiancaYolo: isset($dados['confianca_yolo']) ? (float) $dados['confianca_yolo'] : null,
                captureId: $dados['capture_id'],
                quadrosConfirmados: $dados['quadros_confirmados'] ?? null,
                modeloPlaca: $dados['modelo_placa'] ?? null,
                dataHora: $dados['capturado_em'],
                observacoes: $dados['observacoes'] ?? null,
                metadata: ['candidatos' => $dados['candidatos'] ?? null],
            );

            if ($resultado->duplicado && is_string($caminho)) {
                Storage::disk((string) config('guarita.capturas_disk', 'local'))->delete($caminho);
            }

            $triagem = $triagens->criarSeNecessaria($resultado->acesso);
            $ponto->forceFill([
                'ultima_comunicacao_em' => now(),
                'versao_camera' => $dados['versao_camera'] ?? $ponto->versao_camera,
            ])->saveQuietly();

            return response()->json([
                'message' => $resultado->duplicado ? 'Captura já processada.' : 'Reconhecimento registrado.',
                'duplicado' => $resultado->duplicado,
                'decisao' => $triagem !== null ? 'triagem' : ($resultado->acesso->permitePassagem() ? 'autorizado' : 'negado'),
                'triagem_id' => $triagem?->id,
                'data' => new AcessoResource($resultado->acesso),
            ], $resultado->duplicado ? 200 : 201);
        } catch (\Throwable $exception) {
            if (is_string($caminho)) {
                Storage::disk((string) config('guarita.capturas_disk', 'local'))->delete($caminho);
            }

            throw $exception;
        }
    }

    public function heartbeat(Request $request): JsonResponse
    {
        /** @var PontoAcesso $ponto */
        $ponto = $request->attributes->get('camera_ponto_acesso');
        $ponto->forceFill(['ultima_comunicacao_em' => now()])->saveQuietly();

        return response()->json(['status' => 'online', 'server_time' => now()->toIso8601String()]);
    }
}
