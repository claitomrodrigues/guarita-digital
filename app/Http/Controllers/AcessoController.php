<?php

namespace App\Http\Controllers;

use App\Http\Requests\LiberarAcessoRequest;
use App\Http\Requests\ListAcessosRequest;
use App\Http\Requests\RegistrarAcessoManualRequest;
use App\Http\Resources\AcessoResource;
use App\Models\Acesso;
use App\Models\PontoAcesso;
use App\Models\Veiculo;
use App\Services\AcessoService;
use App\Services\TriagemService;
use App\Support\Placa;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcessoController extends Controller
{
    public function index(ListAcessosRequest $request): AnonymousResourceCollection
    {
        $dados = $request->validated();
        $query = Acesso::query()
            ->with(['veiculo.pessoa', 'pessoa', 'usuario', 'pontoAcesso'])
            ->latest('data_hora')
            ->latest('id');

        if (filled($dados['placa'] ?? null)) {
            $placa = Placa::normalizar($dados['placa']);
            $query->where('placa_reconhecida', 'like', "%{$placa}%");
        }

        foreach (['tipo', 'status', 'origem', 'veiculo_id', 'pessoa_id', 'ponto_acesso_id'] as $campo) {
            if (isset($dados[$campo])) {
                $query->where($campo, $dados[$campo]);
            }
        }

        $inicio = isset($dados['data_inicio'])
            ? CarbonImmutable::parse($dados['data_inicio'])->startOfDay()
            : null;
        $fim = isset($dados['data_fim'])
            ? CarbonImmutable::parse($dados['data_fim'])->endOfDay()
            : null;

        $query->noPeriodo($inicio, $fim);

        return AcessoResource::collection($query->paginate($dados['per_page'] ?? 20)->withQueryString());
    }

    public function show(Acesso $acesso): AcessoResource
    {
        $this->authorize('view', $acesso);

        return new AcessoResource(
            $acesso->load(['veiculo.pessoa', 'pessoa', 'usuario', 'pontoAcesso']),
        );
    }

    public function registrarManual(
        RegistrarAcessoManualRequest $request,
        AcessoService $service,
        TriagemService $triagens,
    ): JsonResponse {
        $dados = $request->validated();
        $liberarManualmente = $request->boolean('liberar_manualmente');

        if ($liberarManualmente && ! Veiculo::query()->where('placa', $dados['placa'])->exists()) {
            throw ValidationException::withMessages([
                'placa' => 'Só é possível liberar manualmente um veículo cadastrado.',
            ]);
        }

        $ponto = isset($dados['ponto_acesso_id'])
            ? PontoAcesso::query()->find($dados['ponto_acesso_id'])
            : null;

        $resultado = $service->registrarManual(
            placa: $dados['placa'],
            tipo: \App\Enums\TipoAcesso::from($dados['tipo']),
            usuario: $request->user(),
            pontoAcesso: $ponto,
            dataHora: $dados['data_hora'] ?? now(),
            liberarManualmente: $liberarManualmente,
            observacoes: $dados['observacoes'] ?? null,
        );
        $triagem = $triagens->criarSeNecessaria($resultado->acesso);

        return response()->json([
            'message' => $resultado->duplicado
                ? 'Registro duplicado ignorado; o acesso anterior foi mantido.'
                : 'Acesso registrado com sucesso.',
            'duplicado' => $resultado->duplicado,
            'data' => new AcessoResource($resultado->acesso),
            'triagem_id' => $triagem?->id,
        ], $resultado->duplicado ? 200 : 201);
    }

    public function liberar(
        LiberarAcessoRequest $request,
        Acesso $acesso,
        AcessoService $service,
    ): AcessoResource {
        $acesso = $service->liberarManualmente(
            acesso: $acesso,
            usuario: $request->user(),
            observacoes: $request->validated('observacoes'),
        );

        return new AcessoResource($acesso);
    }

    public function imagem(Acesso $acesso): StreamedResponse
    {
        $this->authorize('view', $acesso);
        abort_unless(filled($acesso->imagem), 404);

        $disk = Storage::disk((string) config('guarita.capturas_disk', 'local'));
        abort_unless($disk->exists($acesso->imagem), 404);

        return $disk->response(
            $acesso->imagem,
            basename((string) $acesso->imagem),
            ['Cache-Control' => 'private, max-age=300'],
        );
    }
}
