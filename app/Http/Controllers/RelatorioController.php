<?php

namespace App\Http\Controllers;

use App\Enums\OrigemAcesso;
use App\Enums\StatusAcesso;
use App\Enums\TipoAcesso;
use App\Http\Requests\ListAcessosRequest;
use App\Models\Acesso;
use App\Models\PontoAcesso;
use App\Support\Placa;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController extends Controller
{
    public function index(ListAcessosRequest $request): View
    {
        $filtros = $this->filtros($request);
        $consulta = $this->consulta($filtros);

        return view('relatorios.index', [
            ...$this->dadosRelatorio($consulta, $filtros),
            'acessos' => (clone $consulta)
                ->with(['veiculo.pessoa', 'pessoa', 'pontoAcesso', 'usuario'])
                ->latest('data_hora')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'pontosAcesso' => PontoAcesso::query()->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function pdf(ListAcessosRequest $request): Response
    {
        $filtros = $this->filtros($request);
        $consulta = $this->consulta($filtros);
        $dados = $this->dadosRelatorio($consulta, $filtros);
        $dados['acessos'] = (clone $consulta)
            ->with(['veiculo.pessoa', 'pessoa', 'pontoAcesso', 'usuario'])
            ->oldest('data_hora')
            ->oldest('id')
            ->get();

        return Pdf::loadView('relatorios.pdf', $dados)
            ->setPaper('a4', 'landscape')
            ->download('relatorio-acessos-'.$filtros['data_inicio'].'-'.$filtros['data_fim'].'.pdf');
    }

    public function acessosCsv(ListAcessosRequest $request): StreamedResponse
    {
        $query = $this->consulta($this->filtros($request))
            ->with(['veiculo.pessoa', 'pontoAcesso', 'usuario'])
            ->oldest('data_hora');

        return response()->streamDownload(function () use ($query): void {
            $arquivo = fopen('php://output', 'wb');
            fwrite($arquivo, "\xEF\xBB\xBF");
            fputcsv($arquivo, ['Data e hora', 'Placa', 'Tipo', 'Status', 'Pessoa', 'Ponto de acesso', 'Confiança OCR', 'Confiança YOLO'], ';');

            $query->chunkById(500, function ($acessos) use ($arquivo): void {
                foreach ($acessos as $acesso) {
                    fputcsv($arquivo, [
                        $acesso->data_hora?->format('d/m/Y H:i:s'),
                        Placa::formatar($acesso->placa_reconhecida),
                        $acesso->tipo?->label(),
                        $acesso->status?->label(),
                        $acesso->veiculo?->pessoa?->nome,
                        $acesso->pontoAcesso?->nome,
                        $acesso->confianca,
                        $acesso->confianca_yolo,
                    ], ';');
                }
            });

            fclose($arquivo);
        }, 'acessos-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filtros(ListAcessosRequest $request): array
    {
        $filtros = $request->validated();
        $filtros['data_inicio'] ??= now()->subDays(29)->toDateString();
        $filtros['data_fim'] ??= now()->toDateString();

        return $filtros;
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return Builder<Acesso>
     */
    private function consulta(array $filtros): Builder
    {
        $query = Acesso::query();

        if (filled($filtros['placa'] ?? null)) {
            $query->where('placa_reconhecida', 'like', '%'.Placa::normalizar($filtros['placa']).'%');
        }

        foreach (['tipo', 'status', 'origem', 'veiculo_id', 'pessoa_id', 'ponto_acesso_id'] as $campo) {
            $query->when($filtros[$campo] ?? null, fn (Builder $q, mixed $valor) => $q->where($campo, $valor));
        }

        return $query->noPeriodo(
            CarbonImmutable::parse($filtros['data_inicio'])->startOfDay(),
            CarbonImmutable::parse($filtros['data_fim'])->endOfDay(),
        );
    }

    /**
     * @param  Builder<Acesso>  $consulta
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    private function dadosRelatorio(Builder $consulta, array $filtros): array
    {
        $total = (clone $consulta)->count();
        $entradas = (clone $consulta)->where('tipo', TipoAcesso::Entrada->value)->count();
        $saidas = (clone $consulta)->where('tipo', TipoAcesso::Saida->value)->count();
        $autorizados = (clone $consulta)->whereIn('status', [
            StatusAcesso::Autorizado->value,
            StatusAcesso::LiberadoManualmente->value,
        ])->count();

        $porStatus = (clone $consulta)
            ->selectRaw('status as codigo, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn (object $item): array => [
                'label' => StatusAcesso::tryFrom($item->codigo)?->label() ?? $item->codigo,
                'total' => (int) $item->total,
            ])
            ->all();

        $porOrigem = (clone $consulta)
            ->selectRaw('origem as codigo, COUNT(*) as total')
            ->groupBy('origem')
            ->orderByDesc('total')
            ->get()
            ->map(fn (object $item): array => [
                'label' => OrigemAcesso::tryFrom($item->codigo)?->label() ?? $item->codigo,
                'total' => (int) $item->total,
            ])
            ->all();

        $agregados = (clone $consulta)
            ->selectRaw('DATE(data_hora) as periodo, tipo as movimento, COUNT(*) as total')
            ->groupByRaw('DATE(data_hora), tipo')
            ->orderBy('periodo')
            ->get();
        $inicio = CarbonImmutable::parse($filtros['data_inicio']);
        $fim = CarbonImmutable::parse($filtros['data_fim']);
        $agruparPorMes = $inicio->diffInDays($fim) > 90;
        $movimentos = [];

        foreach ($agregados as $item) {
            $dia = CarbonImmutable::parse($item->periodo);
            $chave = $agruparPorMes ? $dia->format('Y-m') : $dia->toDateString();
            $movimentos[$chave] ??= ['label' => $agruparPorMes ? $dia->format('m/Y') : $dia->format('d/m'), 'total' => 0];
            $movimentos[$chave][$item->movimento] = (int) $item->total;
            $movimentos[$chave]['total'] += (int) $item->total;
        }

        $inicioPeriodo = $agruparPorMes ? $inicio->startOfMonth() : $inicio->startOfDay();
        $fimPeriodo = $agruparPorMes ? $fim->startOfMonth() : $fim->startOfDay();
        $movimentosCompletos = [];

        for ($periodo = $inicioPeriodo; $periodo <= $fimPeriodo; $periodo = $agruparPorMes ? $periodo->addMonth() : $periodo->addDay()) {
            $chave = $agruparPorMes ? $periodo->format('Y-m') : $periodo->toDateString();
            $movimentosCompletos[] = $movimentos[$chave] ?? [
                'label' => $agruparPorMes ? $periodo->format('m/Y') : $periodo->format('d/m'),
                'total' => 0,
                TipoAcesso::Entrada->value => 0,
                TipoAcesso::Saida->value => 0,
            ];
        }

        return [
            'filtros' => $filtros,
            'totalAcessos' => $total,
            'totalEntradas' => $entradas,
            'totalSaidas' => $saidas,
            'totalAutorizados' => $autorizados,
            'totalNegados' => $total - $autorizados,
            'porStatus' => $this->comPercentuais($porStatus),
            'porOrigem' => $this->comPercentuais($porOrigem),
            'movimentos' => $movimentosCompletos,
        ];
    }

    /**
     * @param  Collection<int, array{label: string, total: int}>|array<int, array{label: string, total: int}>  $itens
     * @return array<int, array{label: string, total: int, percentual: int}>
     */
    private function comPercentuais(array $itens): array
    {
        $maximo = max(1, ...array_column($itens, 'total'));

        return array_map(
            fn (array $item): array => [...$item, 'percentual' => (int) round($item['total'] / $maximo * 100)],
            $itens,
        );
    }
}
