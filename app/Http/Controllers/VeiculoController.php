<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListVeiculosRequest;
use App\Http\Requests\StoreVeiculoRequest;
use App\Http\Requests\UpdateVeiculoRequest;
use App\Http\Resources\VeiculoResource;
use App\Models\Veiculo;
use App\Support\Placa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VeiculoController extends Controller
{
    public function index(ListVeiculosRequest $request): AnonymousResourceCollection
    {
        $dados = $request->validated();
        $query = Veiculo::query()->with('pessoa')->latest('updated_at');

        if (filled($dados['q'] ?? null)) {
            $busca = trim($dados['q']);
            $placa = Placa::normalizar($busca);

            $query->where(function (Builder $query) use ($busca, $placa): void {
                $query
                    ->when($placa !== '', static fn (Builder $query): Builder => $query->where('placa', 'like', "%{$placa}%"))
                    ->orWhere('marca', 'like', "%{$busca}%")
                    ->orWhere('modelo', 'like', "%{$busca}%")
                    ->orWhere('cor', 'like', "%{$busca}%")
                    ->orWhereHas('pessoa', static fn (Builder $pessoa): Builder => $pessoa->where('nome', 'like', "%{$busca}%"));
            });
        }

        foreach (['pessoa_id', 'tipo'] as $campo) {
            if (isset($dados[$campo])) {
                $query->where($campo, $dados[$campo]);
            }
        }

        foreach (['ativo', 'autorizado'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $query->where($campo, (bool) $dados[$campo]);
            }
        }

        return VeiculoResource::collection($query->paginate($dados['per_page'] ?? 15)->withQueryString());
    }

    public function store(StoreVeiculoRequest $request): JsonResponse
    {
        $veiculo = Veiculo::query()->create($request->validated());

        return (new VeiculoResource($veiculo->load('pessoa')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Veiculo $veiculo): VeiculoResource
    {
        $this->authorize('view', $veiculo);

        return new VeiculoResource($veiculo->load('pessoa'));
    }

    public function update(UpdateVeiculoRequest $request, Veiculo $veiculo): VeiculoResource
    {
        $veiculo->update($request->validated());

        return new VeiculoResource($veiculo->refresh()->load('pessoa'));
    }

    public function destroy(Veiculo $veiculo): JsonResponse
    {
        $this->authorize('delete', $veiculo);
        $veiculo->delete();

        return response()->json(status: 204);
    }
}
