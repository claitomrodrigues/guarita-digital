<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListLogsAuditoriaRequest;
use App\Http\Resources\LogAuditoriaResource;
use App\Models\LogAuditoria;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LogAuditoriaController extends Controller
{
    public function index(ListLogsAuditoriaRequest $request): AnonymousResourceCollection
    {
        $dados = $request->validated();
        $query = LogAuditoria::query()->with('usuario')->latest('created_at');

        if (filled($dados['q'] ?? null)) {
            $busca = trim($dados['q']);
            $query->where(static function (Builder $query) use ($busca): void {
                $query
                    ->where('entidade', 'like', "%{$busca}%")
                    ->orWhere('acao', 'like', "%{$busca}%");

                if (ctype_digit($busca)) {
                    $query->orWhere('entidade_id', (int) $busca);
                }
            });
        }

        if (isset($dados['acao'])) {
            $query->where('acao', $dados['acao']);
        }

        if (isset($dados['user_id'])) {
            $query->where('user_id', $dados['user_id']);
        }

        if (isset($dados['data_inicio'])) {
            $query->where('created_at', '>=', CarbonImmutable::parse($dados['data_inicio'])->startOfDay());
        }

        if (isset($dados['data_fim'])) {
            $query->where('created_at', '<=', CarbonImmutable::parse($dados['data_fim'])->endOfDay());
        }

        return LogAuditoriaResource::collection(
            $query->paginate($dados['per_page'] ?? 30)->withQueryString(),
        );
    }
}
