<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListPessoasRequest;
use App\Http\Requests\StorePessoaRequest;
use App\Http\Requests\UpdatePessoaRequest;
use App\Http\Resources\PessoaResource;
use App\Models\Pessoa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PessoaController extends Controller
{
    public function index(ListPessoasRequest $request): AnonymousResourceCollection
    {
        $dados = $request->validated();
        $query = Pessoa::query()->withCount('veiculos')->orderBy('nome');

        if (filled($dados['q'] ?? null)) {
            $busca = trim($dados['q']);
            $somenteNumeros = preg_replace('/\D+/', '', $busca) ?? '';

            $query->where(function (Builder $query) use ($busca, $somenteNumeros): void {
                $query
                    ->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%")
                    ->orWhere('matricula', 'like', "%{$busca}%")
                    ->when($somenteNumeros !== '', static fn (Builder $query): Builder => $query->orWhere('cpf', 'like', "%{$somenteNumeros}%"));
            });
        }

        if (isset($dados['tipo_vinculo'])) {
            $query->where('tipo_vinculo', $dados['tipo_vinculo']);
        }

        if (array_key_exists('ativo', $dados)) {
            $query->where('ativo', (bool) $dados['ativo']);
        }

        return PessoaResource::collection($query->paginate($dados['per_page'] ?? 15)->withQueryString());
    }

    public function store(StorePessoaRequest $request): JsonResponse
    {
        $pessoa = Pessoa::query()->create($request->validated());

        return (new PessoaResource($pessoa))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Pessoa $pessoa): PessoaResource
    {
        $this->authorize('view', $pessoa);

        return new PessoaResource($pessoa->load(['veiculos' => fn ($query) => $query->orderBy('placa')]));
    }

    public function update(UpdatePessoaRequest $request, Pessoa $pessoa): PessoaResource
    {
        $pessoa->update($request->validated());

        return new PessoaResource($pessoa->refresh()->load('veiculos'));
    }

    public function destroy(Pessoa $pessoa): JsonResponse
    {
        $this->authorize('delete', $pessoa);

        if ($pessoa->veiculos()->exists()) {
            return response()->json([
                'message' => 'A pessoa possui veículos vinculados. Transfira ou exclua esses veículos antes de continuar.',
            ], 422);
        }

        $pessoa->delete();

        return response()->json(status: 204);
    }
}
