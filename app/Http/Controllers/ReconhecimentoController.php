<?php

namespace App\Http\Controllers;

use App\Enums\TipoAcesso;
use App\Exceptions\ReconhecimentoPlacaException;
use App\Http\Requests\ReconhecerPlacaRequest;
use App\Http\Resources\AcessoResource;
use App\Models\PontoAcesso;
use App\Services\AcessoService;
use App\Services\ReconhecimentoPlacaService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ReconhecimentoController extends Controller
{
    public function reconhecer(
        ReconhecerPlacaRequest $request,
        ReconhecimentoPlacaService $reconhecimento,
        AcessoService $acessos,
    ): JsonResponse {
        $arquivo = $request->file('imagem');
        $diskName = (string) config('guarita.capturas_disk', 'local');
        $disk = Storage::disk($diskName);
        $diretorio = 'capturas/'.now()->format('Y/m/d');
        $extensao = $arquivo->guessExtension() ?: 'jpg';
        $nome = Str::uuid()->toString().'.'.$extensao;
        $caminhoRelativo = $arquivo->storeAs($diretorio, $nome, $diskName);

        if ($caminhoRelativo === false) {
            return response()->json([
                'message' => 'Não foi possível armazenar a imagem capturada.',
            ], 500);
        }

        $manterImagem = false;

        try {
            $caminhoAbsoluto = $disk->path($caminhoRelativo);
            $placa = $reconhecimento->reconhecer($caminhoAbsoluto);
            $dados = $request->validated();
            $ponto = isset($dados['ponto_acesso_id'])
                ? PontoAcesso::query()->find($dados['ponto_acesso_id'])
                : null;

            $dimensoes = @getimagesize($caminhoAbsoluto);

            $resultado = $acessos->registrarReconhecimento(
                placa: $placa,
                usuario: $request->user(),
                pontoAcesso: $ponto,
                tipoSolicitado: isset($dados['tipo']) ? TipoAcesso::from($dados['tipo']) : null,
                imagem: $caminhoRelativo,
                observacoes: $dados['observacoes'] ?? null,
                metadata: [
                    'arquivo_original' => Str::limit(basename($arquivo->getClientOriginalName()), 255, ''),
                    'mime_type' => $arquivo->getMimeType(),
                    'tamanho_bytes' => $arquivo->getSize(),
                    'largura' => is_array($dimensoes) ? ($dimensoes[0] ?? null) : null,
                    'altura' => is_array($dimensoes) ? ($dimensoes[1] ?? null) : null,
                ],
            );

            $manterImagem = ! $resultado->duplicado;

            return response()->json([
                'message' => $resultado->duplicado
                    ? 'Leitura duplicada ignorada; o acesso anterior foi mantido.'
                    : 'Placa reconhecida e acesso registrado.',
                'duplicado' => $resultado->duplicado,
                'data' => new AcessoResource($resultado->acesso),
            ], $resultado->duplicado ? 200 : 201);
        } catch (ReconhecimentoPlacaException|DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Ocorreu uma falha interna ao processar a imagem.',
            ], 500);
        } finally {
            if (! $manterImagem) {
                $disk->delete($caminhoRelativo);
            }
        }
    }
}
