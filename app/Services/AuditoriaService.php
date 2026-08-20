<?php

namespace App\Services;

use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class AuditoriaService
{
    private const CAMPOS_SENSIVEIS = [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
        'secret',
    ];

    public function registrar(
        string $acao,
        Model|string $entidade,
        int|string|null $entidadeId = null,
        ?array $dadosAnteriores = null,
        ?array $dadosNovos = null,
        ?User $usuario = null,
    ): void {
        try {
            $modelo = $entidade instanceof Model ? $entidade : null;
            $request = app()->bound('request') ? request() : null;

            LogAuditoria::query()->create([
                'user_id' => $usuario?->id ?? auth()->id(),
                'acao' => Str::limit($acao, 40, ''),
                'entidade' => $modelo?->getMorphClass() ?? $entidade,
                'entidade_id' => $entidadeId ?? $modelo?->getKey(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent()
                    ? Str::limit((string) $request->userAgent(), 1000, '')
                    : null,
                'dados_anteriores' => $this->proteger($dadosAnteriores),
                'dados_novos' => $this->proteger($dadosNovos),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function proteger(?array $dados): ?array
    {
        if ($dados === null) {
            return null;
        }

        $dados = Arr::except($dados, ['updated_at']);

        foreach (self::CAMPOS_SENSIVEIS as $campo) {
            if (array_key_exists($campo, $dados)) {
                $dados[$campo] = '[PROTEGIDO]';
            }
        }

        return $dados;
    }
}
