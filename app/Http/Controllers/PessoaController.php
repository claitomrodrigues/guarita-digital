<?php

namespace App\Http\Controllers;

use App\Enums\TipoVinculo;
use App\Http\Requests\StorePessoaRequest;
use App\Http\Requests\UpdatePessoaRequest;
use App\Models\Pessoa;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PessoaController extends Controller
{
    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('q'));

        $condutores = Pessoa::query()
            ->withCount('veiculos')
            ->when($busca !== '', function (Builder $query) use ($busca): void {
                $numeros = preg_replace('/\D+/', '', $busca) ?? '';

                $query->where(function (Builder $query) use ($busca, $numeros): void {
                    $query
                        ->where('nome', 'like', "%{$busca}%")
                        ->orWhere('matricula', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%")
                        ->when($numeros !== '', fn (Builder $query): Builder => $query->orWhere('cpf', 'like', "%{$numeros}%"));
                });
            })
            ->orderBy('nome')
            ->paginate(10)
            ->withQueryString();

        return view('condutores.index', compact('condutores', 'busca'));
    }

    public function create(): View
    {
        return view('condutores.create', ['tiposVinculo' => TipoVinculo::cases()]);
    }

    public function store(StorePessoaRequest $request): RedirectResponse
    {
        Pessoa::query()->create($request->validated());

        return redirect()
            ->route('condutores.index')
            ->with('success', 'Condutor cadastrado com sucesso.');
    }

    public function edit(Pessoa $pessoa): View
    {
        return view('condutores.edit', [
            'condutor' => $pessoa,
            'tiposVinculo' => TipoVinculo::cases(),
        ]);
    }

    public function update(UpdatePessoaRequest $request, Pessoa $pessoa): RedirectResponse
    {
        $pessoa->update($request->validated());

        return redirect()
            ->route('condutores.index')
            ->with('success', 'Condutor atualizado com sucesso.');
    }

    public function destroy(Pessoa $pessoa): RedirectResponse
    {
        if ($pessoa->veiculos()->exists()) {
            return back()->with('error', 'Este condutor possui veículo vinculado e não pode ser excluído.');
        }

        $pessoa->delete();

        return redirect()
            ->route('condutores.index')
            ->with('success', 'Condutor excluído com sucesso.');
    }
}
