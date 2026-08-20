<?php

namespace App\Http\Controllers;

use App\Enums\StatusAcesso;
use App\Enums\TipoAcesso;
use App\Http\Resources\AcessoResource;
use App\Models\Acesso;
use App\Models\Pessoa;
use App\Models\Veiculo;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $inicioHoje = now()->startOfDay();
        $fimHoje = now()->endOfDay();
        $inicioSerie = now()->subDays(6)->startOfDay();

        $presentes = Acesso::query()
            ->from('acessos as atual')
            ->where('atual.tipo', TipoAcesso::Entrada->value)
            ->whereIn('atual.status', [
                StatusAcesso::Autorizado->value,
                StatusAcesso::LiberadoManualmente->value,
            ])
            ->whereNotExists(function (QueryBuilder $query): void {
                $query
                    ->selectRaw('1')
                    ->from('acessos as posterior')
                    ->whereColumn('posterior.placa_reconhecida', 'atual.placa_reconhecida')
                    ->where(function (QueryBuilder $query): void {
                        $query
                            ->whereColumn('posterior.data_hora', '>', 'atual.data_hora')
                            ->orWhere(function (QueryBuilder $query): void {
                                $query
                                    ->whereColumn('posterior.data_hora', '=', 'atual.data_hora')
                                    ->whereColumn('posterior.id', '>', 'atual.id');
                            });
                    });
            })
            ->count();

        $movimentacao = Acesso::query()
            ->selectRaw('DATE(data_hora) as dia')
            ->selectRaw('SUM(CASE WHEN tipo = ? THEN 1 ELSE 0 END) as entradas', [TipoAcesso::Entrada->value])
            ->selectRaw('SUM(CASE WHEN tipo = ? THEN 1 ELSE 0 END) as saidas', [TipoAcesso::Saida->value])
            ->where('data_hora', '>=', $inicioSerie)
            ->groupBy(DB::raw('DATE(data_hora)'))
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');

        $serie = [];

        foreach (CarbonPeriod::create($inicioSerie, now()->startOfDay()) as $dia) {
            $chave = $dia->toDateString();
            $registro = $movimentacao->get($chave);

            $serie[] = [
                'data' => $chave,
                'entradas' => (int) ($registro?->entradas ?? 0),
                'saidas' => (int) ($registro?->saidas ?? 0),
            ];
        }

        $ultimos = Acesso::query()
            ->with(['veiculo.pessoa', 'pessoa', 'usuario', 'pontoAcesso'])
            ->latest('data_hora')
            ->latest('id')
            ->limit(10)
            ->get();

        $acessosHoje = Acesso::query()->whereBetween('data_hora', [$inicioHoje, $fimHoje]);

        return response()->json([
            'resumo' => [
                'pessoas_ativas' => Pessoa::query()->ativas()->count(),
                'veiculos_ativos' => Veiculo::query()->ativos()->count(),
                'veiculos_autorizados' => Veiculo::query()->aptosAoAcesso()->count(),
                'veiculos_presentes' => $presentes,
                'entradas_hoje' => (clone $acessosHoje)->where('tipo', TipoAcesso::Entrada->value)->count(),
                'saidas_hoje' => (clone $acessosHoje)->where('tipo', TipoAcesso::Saida->value)->count(),
                'nao_cadastrados_hoje' => (clone $acessosHoje)->where('status', StatusAcesso::NaoCadastrado->value)->count(),
                'bloqueados_hoje' => (clone $acessosHoje)->where('status', StatusAcesso::Bloqueado->value)->count(),
            ],
            'movimentacao_7_dias' => $serie,
            'ultimos_acessos' => AcessoResource::collection($ultimos),
            'gerado_em' => now()->toIso8601String(),
        ]);
    }
}
