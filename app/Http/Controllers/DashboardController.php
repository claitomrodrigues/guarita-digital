<?php

namespace App\Http\Controllers;
use App\Enums\TipoAcesso;
use App\Models\Acesso;
use App\Models\Veiculo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(): View|JsonResponse
    {
        $inicioHoje = Carbon::today();
        $fimHoje = Carbon::tomorrow();

        $acessosHoje = Acesso::query()
            ->where('data_hora', '>=', $inicioHoje)
            ->where('data_hora', '<', $fimHoje)
            ->get(['tipo', 'data_hora']);

        $movimentacoesPorHora = collect(range(0, 23))
            ->map(fn (int $hora): array => [
                'hora' => sprintf('%02d:00', $hora),
                'entradas' => 0,
                'saidas' => 0,
            ])
            ->all();

        foreach ($acessosHoje as $acesso) {
            $hora = (int) $acesso->data_hora->format('G');
            $coluna = $acesso->tipo === TipoAcesso::Entrada ? 'entradas' : 'saidas';
            $movimentacoesPorHora[$hora][$coluna]++;
        }

        $totalEntradasHoje = array_sum(array_column($movimentacoesPorHora, 'entradas'));
        $totalSaidasHoje = array_sum(array_column($movimentacoesPorHora, 'saidas'));

        $picoEntrada = collect($movimentacoesPorHora)->sortByDesc('entradas')->first();
        $picoSaida = collect($movimentacoesPorHora)->sortByDesc('saidas')->first();

        $picoEntrada = $picoEntrada['entradas'] > 0 ? $picoEntrada['hora'] : '—';
        $picoSaida = $picoSaida['saidas'] > 0 ? $picoSaida['hora'] : '—';

        $acessosRecentes = Acesso::query()
            ->with('veiculo')
            ->latest('data_hora')
            ->limit(10)
            ->get()
            ->map(fn (Acesso $acesso): array => [
                'horario' => $acesso->data_hora->format('H:i'),
                'placa' => $acesso->placa_reconhecida,
                'veiculo' => trim(implode(' ', array_filter([
                    $acesso->veiculo?->marca,
                    $acesso->veiculo?->modelo,
                ]))) ?: '—',
                'tipo' => $acesso->tipo?->value ?? '',
                'autorizado' => $acesso->status?->permitePassagem() ?? false,
            ])
            ->all();

        $acessosNoPatio = Acesso::query()
            ->with('veiculo')
            ->where('tipo', TipoAcesso::Entrada)
            ->where('id', '=', function ($query): void {
                $query->select('ultimo_acesso.id')
                    ->from('acessos as ultimo_acesso')
                    ->whereColumn('ultimo_acesso.placa_reconhecida', 'acessos.placa_reconhecida')
                    ->orderByDesc('ultimo_acesso.data_hora')
                    ->orderByDesc('ultimo_acesso.id')
                    ->limit(1);
            })
            ->latest('data_hora')
            ->get()
            ->map(fn (Acesso $acesso): array => [
                'horario' => $acesso->data_hora->format('d/m/Y H:i'),
                'placa' => $acesso->placa_reconhecida,
                'veiculo' => trim(implode(' ', array_filter([
                    $acesso->veiculo?->marca,
                    $acesso->veiculo?->modelo,
                ]))) ?: '—',
            ])
            ->all();

        $dados = [
            'totalVeiculos' => Veiculo::query()->count(),
            'veiculosAutorizados' => Veiculo::query()
                ->where('ativo', true)
                ->where('autorizado', true)
                ->count(),
            'totalEntradasHoje' => $totalEntradasHoje,
            'totalSaidasHoje' => $totalSaidasHoje,
            'veiculosNoPatio' => count($acessosNoPatio),
            'veiculosNoPatioLista' => $acessosNoPatio,
            'movimentacoesPorHora' => $movimentacoesPorHora,
            'movimentacoesRecentes' => $acessosRecentes,
            'picoEntrada' => $picoEntrada,
            'picoSaida' => $picoSaida,
        ];

        if (request()->expectsJson()) {
            return response()->json($dados);
        }

        return view('home', $dados);
    }
}