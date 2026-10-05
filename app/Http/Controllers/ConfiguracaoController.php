<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateConfiguracaoRequest;
use App\Http\Resources\ConfiguracaoSistemaResource;
use App\Models\ConfiguracaoSistema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConfiguracaoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ConfiguracaoSistema::class);

        return ConfiguracaoSistemaResource::collection(
            ConfiguracaoSistema::query()->orderBy('grupo')->orderBy('chave')->get(),
        );
    }

    public function update(
        UpdateConfiguracaoRequest $request,
        ConfiguracaoSistema $configuracao,
    ): ConfiguracaoSistemaResource {
        $configuracao->update($request->validated());
        ConfiguracaoSistema::esquecer($configuracao->chave);

        return new ConfiguracaoSistemaResource($configuracao->refresh());
    }

    public function publicas(): JsonResponse
    {
        $configuracoes = ConfiguracaoSistema::query()
            ->publicas()
            ->orderBy('chave')
            ->get()
            ->mapWithKeys(static fn (ConfiguracaoSistema $item): array => [
                $item->chave => $item->valorConvertido(),
            ]);

        return response()->json(['data' => $configuracoes]);
    }
}
