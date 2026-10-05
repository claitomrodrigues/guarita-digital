<?php

namespace App\Http\Controllers;

use App\Enums\TipoVeiculo;
use App\Http\Requests\StoreVeiculoRequest;
use App\Http\Requests\UpdateVeiculoRequest;
use App\Models\Pessoa;
use App\Models\Veiculo;
use App\Support\Placa;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VeiculoController extends Controller
{
    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('q'));

        $veiculos = Veiculo::query()
            ->with('pessoa')
            ->when($busca !== '', function (Builder $query) use ($busca): void {
                $placa = Placa::normalizar($busca);

                $query->where(function (Builder $query) use ($busca, $placa): void {
                    $query
                        ->where('placa', 'like', "%{$placa}%")
                        ->orWhere('marca', 'like', "%{$busca}%")
                        ->orWhere('modelo', 'like', "%{$busca}%")
                        ->orWhereHas('pessoa', fn (Builder $pessoa): Builder => $pessoa->where('nome', 'like', "%{$busca}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('veiculos.index', compact('veiculos', 'busca'));
    }

    public function create(): View
    {
        return view('veiculos.create', $this->dadosFormulario());
    }

    public function store(StoreVeiculoRequest $request): RedirectResponse
    {
        Veiculo::query()->create($request->validated());

        return redirect()
            ->route('veiculos.index')
            ->with('success', 'Veículo cadastrado com sucesso.');
    }

    public function edit(Veiculo $veiculo): View
    {
        return view('veiculos.edit', [
            ...$this->dadosFormulario(),
            'veiculo' => $veiculo,
        ]);
    }

    public function update(UpdateVeiculoRequest $request, Veiculo $veiculo): RedirectResponse
    {
        $veiculo->update($request->validated());

        return redirect()
            ->route('veiculos.index')
            ->with('success', 'Veículo atualizado com sucesso.');
    }

    public function destroy(Veiculo $veiculo): RedirectResponse
    {
        $veiculo->delete();

        return redirect()
            ->route('veiculos.index')
            ->with('success', 'Veículo excluído com sucesso.');
    }

    /** @return array{condutores: \Illuminate\Database\Eloquent\Collection, tiposVeiculo: array} */
    private function dadosFormulario(): array
    {
        return [
            'condutores' => Pessoa::query()->orderBy('nome')->get(),
            'tiposVeiculo' => TipoVeiculo::cases(),
        ];
    }
}
