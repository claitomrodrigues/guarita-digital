<?php

namespace App\Http\Controllers;

use App\Exceptions\ReconhecimentoPlacaException;
use App\Models\Veiculo;
use App\Services\ReconhecimentoPlacaService;
use App\Support\Placa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CapturaCameraController extends Controller
{
    public function __invoke(Request $request, ReconhecimentoPlacaService $reconhecimento): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'string'],
        ], [
            'image.required' => 'Capture uma imagem antes de iniciar o reconhecimento.',
        ]);

        $imagemBase64 = (string) $request->input('image');
        $limiteBytes = max(1, (int) config('guarita.max_capture_kb', 10240)) * 1024;
        $limiteBase64 = (int) ceil($limiteBytes * 4 / 3) + 1024;

        if (strlen($imagemBase64) > $limiteBase64) {
            return response()->json(['message' => 'A imagem capturada é maior que o limite permitido.'], 422);
        }

        if (preg_match('/\Adata:image\/(png|jpeg|webp);base64,([A-Za-z0-9+\/=\r\n]+)\z/', $imagemBase64, $partes) !== 1) {
            return response()->json(['message' => 'O formato da imagem capturada é inválido.'], 422);
        }

        $conteudo = base64_decode($partes[2], true);

        if ($conteudo === false || strlen($conteudo) > $limiteBytes) {
            return response()->json(['message' => 'Não foi possível ler a imagem capturada.'], 422);
        }

        $informacoes = @getimagesizefromstring($conteudo);
        $extensoes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $mime = is_array($informacoes) ? ($informacoes['mime'] ?? '') : '';

        if (! isset($extensoes[$mime])) {
            return response()->json(['message' => 'A captura não contém uma imagem válida.'], 422);
        }

        $caminhoTemporario = 'capturas-temporarias/'.Str::uuid().'.'.$extensoes[$mime];

        try {
            if (! Storage::disk('local')->put($caminhoTemporario, $conteudo)) {
                throw new ReconhecimentoPlacaException('Não foi possível preparar a captura para o reconhecimento.');
            }

            $placa = $reconhecimento->reconhecer(Storage::disk('local')->path($caminhoTemporario));
            $veiculo = Veiculo::query()->with('pessoa')->where('placa', $placa)->first();
            $autorizado = $veiculo?->estaAutorizado() ?? false;

            return response()->json([
                'placa' => $placa,
                'placa_formatada' => Placa::formatar($placa),
                'cadastrado' => $veiculo !== null,
                'autorizado' => $autorizado,
                'condutor' => $veiculo?->pessoa?->nome,
                'veiculo' => $veiculo !== null
                    ? trim(($veiculo->marca ?? '').' '.($veiculo->modelo ?? ''))
                    : null,
                'message' => match (true) {
                    $veiculo === null => 'Placa reconhecida, mas o veículo não está cadastrado.',
                    $autorizado => 'Veículo cadastrado e autorizado.',
                    default => 'Veículo cadastrado, mas não autorizado.',
                },
            ]);
        } catch (ReconhecimentoPlacaException $excecao) {
            return response()->json(
                ['message' => $excecao->getMessage()],
                422,
                [],
                JSON_INVALID_UTF8_SUBSTITUTE,
            );
        } catch (Throwable $excecao) {
            report($excecao);

            return response()->json(['message' => 'O reconhecimento não pôde ser concluído.'], 500);
        } finally {
            Storage::disk('local')->delete($caminhoTemporario);
        }
    }
}
