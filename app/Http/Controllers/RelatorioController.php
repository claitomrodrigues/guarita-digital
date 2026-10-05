<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListAcessosRequest;
use App\Models\Acesso;
use App\Support\Placa;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController extends Controller
{
    public function acessosCsv(ListAcessosRequest $request): StreamedResponse
    {
        $dados = $request->validated();
        $query = Acesso::query()->with(['veiculo.pessoa', 'pontoAcesso', 'usuario'])->oldest('data_hora');

        if (filled($dados['placa'] ?? null)) {
            $query->where('placa_reconhecida', 'like', '%'.Placa::normalizar($dados['placa']).'%');
        }

        foreach (['tipo', 'status', 'origem', 'veiculo_id', 'pessoa_id', 'ponto_acesso_id'] as $campo) {
            $query->when($dados[$campo] ?? null, fn (Builder $q, mixed $valor) => $q->where($campo, $valor));
        }

        $query->noPeriodo(
            isset($dados['data_inicio']) ? CarbonImmutable::parse($dados['data_inicio'])->startOfDay() : null,
            isset($dados['data_fim']) ? CarbonImmutable::parse($dados['data_fim'])->endOfDay() : null,
        );

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
}
