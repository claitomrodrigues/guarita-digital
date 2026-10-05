<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListPontosAcessoRequest;
use App\Http\Requests\StorePontoAcessoRequest;
use App\Http\Requests\UpdatePontoAcessoRequest;
use App\Http\Resources\PontoAcessoResource;
use App\Models\PontoAcesso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class PontoAcessoController extends Controller
{
    public function index(ListPontosAcessoRequest $request): AnonymousResourceCollection
    {
        $dados = $request->validated();
        $query = PontoAcesso::query()->orderBy('nome');

        if (isset($dados['sentido'])) {
            $query->where('sentido', $dados['sentido']);
        }

        if (array_key_exists('ativo', $dados)) {
            $query->where('ativo', (bool) $dados['ativo']);
        }

        return PontoAcessoResource::collection($query->get());
    }

    public function store(StorePontoAcessoRequest $request): JsonResponse
    {
        $ponto = PontoAcesso::query()->create($request->validated());

        return (new PontoAcessoResource($ponto))
            ->response()
            ->setStatusCode(201);
    }

    public function show(PontoAcesso $ponto_acesso): PontoAcessoResource
    {
        $this->authorize('view', $ponto_acesso);

        return new PontoAcessoResource($ponto_acesso);
    }

    public function update(
        UpdatePontoAcessoRequest $request,
        PontoAcesso $ponto_acesso,
    ): PontoAcessoResource {
        $ponto_acesso->update($request->validated());

        return new PontoAcessoResource($ponto_acesso->refresh());
    }

    public function destroy(PontoAcesso $ponto_acesso): JsonResponse
    {
        $this->authorize('delete', $ponto_acesso);

        if ($ponto_acesso->acessos()->exists()) {
            $ponto_acesso->update(['ativo' => false]);
            $ponto_acesso->delete();

            return response()->json(status: 204);
        }

        $ponto_acesso->delete();

        return response()->json(status: 204);
    }

    public function gerarToken(PontoAcesso $ponto_acesso): JsonResponse
    {
        $this->authorize('update', $ponto_acesso);
        $token = 'gd_'.Str::random(64);
        $ponto_acesso->definirTokenCamera($token);

        return response()->json([
            'message' => 'Token gerado. Copie agora; ele não será exibido novamente.',
            'token' => $token,
        ]);
    }
}
