<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConcluirTriagemRequest;
use App\Http\Requests\ListTriagensRequest;
use App\Http\Resources\TriagemResource;
use App\Models\Triagem;
use App\Services\TriagemService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TriagemController extends Controller
{
    public function index(ListTriagensRequest $request): AnonymousResourceCollection
    {
        $dados = $request->validated();
        $query = Triagem::query()
            ->with(['acesso.veiculo.pessoa', 'acesso.pontoAcesso', 'usuario'])
            ->latest('iniciada_em');

        $query->when($dados['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status));
        $query->when($dados['inicio'] ?? null, fn (Builder $q, string $inicio) => $q->where('iniciada_em', '>=', $inicio));
        $query->when($dados['fim'] ?? null, fn (Builder $q, string $fim) => $q->where('iniciada_em', '<=', $fim));
        $query->when($dados['q'] ?? null, function (Builder $q, string $busca): void {
            $q->where(fn (Builder $q) => $q
                ->where('nome_visitante', 'like', "%{$busca}%")
                ->orWhere('destino', 'like', "%{$busca}%")
                ->orWhereHas('acesso', fn (Builder $a) => $a->where('placa_reconhecida', 'like', "%{$busca}%")));
        });

        return TriagemResource::collection($query->paginate($dados['per_page'] ?? 15)->withQueryString());
    }

    public function show(Triagem $triagem): TriagemResource
    {
        $this->authorize('view', $triagem);

        return new TriagemResource($triagem->load(['acesso.veiculo.pessoa', 'acesso.pontoAcesso', 'usuario']));
    }

    public function concluir(
        ConcluirTriagemRequest $request,
        Triagem $triagem,
        TriagemService $service,
    ): TriagemResource {
        return new TriagemResource($service->concluir($triagem, $request->user(), $request->validated()));
    }
}
