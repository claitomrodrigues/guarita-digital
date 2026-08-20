<?php

namespace App\Services;

use App\Data\RegistroAcessoResultado;
use App\Enums\OrigemAcesso;
use App\Enums\SentidoPontoAcesso;
use App\Enums\StatusAcesso;
use App\Enums\TipoAcesso;
use App\Models\Acesso;
use App\Models\PontoAcesso;
use App\Models\User;
use App\Models\Veiculo;
use App\Support\Placa;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AcessoService
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
    ) {
    }

    public function registrarReconhecimento(
        string $placa,
        User $usuario,
        ?PontoAcesso $pontoAcesso = null,
        ?TipoAcesso $tipoSolicitado = null,
        ?string $imagem = null,
        ?float $confianca = null,
        ?string $observacoes = null,
        ?array $metadata = null,
    ): RegistroAcessoResultado {
        return $this->registrar(
            placa: $placa,
            usuario: $usuario,
            origem: OrigemAcesso::Ocr,
            pontoAcesso: $pontoAcesso,
            tipoSolicitado: $tipoSolicitado,
            imagem: $imagem,
            confianca: $confianca,
            observacoes: $observacoes,
            metadata: $metadata,
        );
    }

    public function registrarManual(
        string $placa,
        TipoAcesso $tipo,
        User $usuario,
        ?PontoAcesso $pontoAcesso = null,
        CarbonInterface|string|null $dataHora = null,
        bool $liberarManualmente = false,
        ?string $observacoes = null,
    ): RegistroAcessoResultado {
        return $this->registrar(
            placa: $placa,
            usuario: $usuario,
            origem: OrigemAcesso::Manual,
            pontoAcesso: $pontoAcesso,
            tipoSolicitado: $tipo,
            dataHora: $dataHora,
            liberarManualmente: $liberarManualmente,
            observacoes: $observacoes,
            metadata: [
                'digitado_manualmente' => true,
            ],
            aplicarDuplicidade: $dataHora === null,
        );
    }

    public function liberarManualmente(
        Acesso $acesso,
        User $usuario,
        ?string $observacoes = null,
    ): Acesso {
        if (! $usuario->ativo) {
            throw new DomainException('O usuário responsável pela liberação está inativo.');
        }

        return DB::transaction(function () use ($acesso, $usuario, $observacoes): Acesso {
            /** @var Acesso $bloqueado */
            $bloqueado = Acesso::query()->lockForUpdate()->findOrFail($acesso->getKey());
            $statusAnterior = $bloqueado->status;

            if ($statusAnterior?->permitePassagem()) {
                return $bloqueado->load(['veiculo.pessoa', 'pessoa', 'usuario', 'pontoAcesso']);
            }

            $metadata = $bloqueado->metadata ?? [];
            $metadata['liberacao_manual'] = [
                'status_anterior' => $statusAnterior?->value,
                'usuario_id' => $usuario->id,
                'data_hora' => now()->toIso8601String(),
            ];

            $bloqueado->update([
                'status' => StatusAcesso::LiberadoManualmente,
                'user_id' => $usuario->id,
                'observacoes' => $observacoes ?? $bloqueado->observacoes,
                'metadata' => $metadata,
            ]);

            $this->auditoria->registrar(
                acao: 'liberacao_manual',
                entidade: $bloqueado,
                dadosAnteriores: ['status' => $statusAnterior?->value],
                dadosNovos: ['status' => StatusAcesso::LiberadoManualmente->value],
                usuario: $usuario,
            );

            return $bloqueado->refresh()->load(['veiculo.pessoa', 'pessoa', 'usuario', 'pontoAcesso']);
        });
    }

    private function registrar(
        string $placa,
        User $usuario,
        OrigemAcesso $origem,
        ?PontoAcesso $pontoAcesso = null,
        ?TipoAcesso $tipoSolicitado = null,
        CarbonInterface|string|null $dataHora = null,
        ?string $imagem = null,
        ?float $confianca = null,
        bool $liberarManualmente = false,
        ?string $observacoes = null,
        ?array $metadata = null,
        bool $aplicarDuplicidade = true,
    ): RegistroAcessoResultado {
        $placa = Placa::normalizar($placa);

        if (! Placa::valida($placa)) {
            throw new DomainException('A placa informada não possui um formato brasileiro válido.');
        }

        if (! $usuario->ativo) {
            throw new DomainException('O usuário responsável pelo registro está inativo.');
        }

        $this->validarPontoAcesso($pontoAcesso);

        $segundosLock = max(5, (int) config('guarita.access_lock_seconds', 15));
        $esperaLock = max(1, (int) config('guarita.access_lock_wait_seconds', 5));
        $chaveLock = 'guarita:acesso:'.hash('sha256', $placa);

        try {
            return Cache::lock($chaveLock, $segundosLock)->block(
                $esperaLock,
                fn (): RegistroAcessoResultado => DB::transaction(function () use (
                    $placa,
                    $usuario,
                    $origem,
                    $pontoAcesso,
                    $tipoSolicitado,
                    $dataHora,
                    $imagem,
                    $confianca,
                    $liberarManualmente,
                    $observacoes,
                    $metadata,
                    $aplicarDuplicidade,
                ): RegistroAcessoResultado {
                    $ultimo = Acesso::query()
                        ->daPlaca($placa)
                        ->latest('data_hora')
                        ->latest('id')
                        ->lockForUpdate()
                        ->first();

                    [$tipo, $duplicado] = $this->resolverTipoEDuplicidade(
                        ultimo: $ultimo,
                        pontoAcesso: $pontoAcesso,
                        tipoSolicitado: $tipoSolicitado,
                        aplicarDuplicidade: $aplicarDuplicidade,
                    );

                    if ($duplicado && $ultimo !== null) {
                        return new RegistroAcessoResultado(
                            acesso: $ultimo->load(['veiculo.pessoa', 'pessoa', 'usuario', 'pontoAcesso']),
                            duplicado: true,
                        );
                    }

                    $veiculo = Veiculo::query()
                        ->with('pessoa')
                        ->where('placa', $placa)
                        ->first();

                    $status = $this->resolverStatus($veiculo, $liberarManualmente);
                    $momento = $dataHora instanceof CarbonInterface
                        ? CarbonImmutable::instance($dataHora)
                        : ($dataHora !== null ? CarbonImmutable::parse($dataHora) : now());

                    $acesso = Acesso::query()->create([
                        'veiculo_id' => $veiculo?->id,
                        'pessoa_id' => $veiculo?->pessoa_id,
                        'user_id' => $usuario->id,
                        'ponto_acesso_id' => $pontoAcesso?->id,
                        'placa_reconhecida' => $placa,
                        'tipo' => $tipo,
                        'status' => $status,
                        'origem' => $origem,
                        'data_hora' => $momento,
                        'imagem' => $imagem,
                        'confianca' => $confianca,
                        'observacoes' => $observacoes,
                        'metadata' => array_filter([
                            'veiculo_encontrado' => $veiculo !== null,
                            'autorizacao_valida' => $veiculo?->estaAutorizado(),
                            'liberacao_solicitada' => $liberarManualmente ?: null,
                            ...($metadata ?? []),
                        ], static fn (mixed $valor): bool => $valor !== null),
                    ]);

                    return new RegistroAcessoResultado(
                        acesso: $acesso->load(['veiculo.pessoa', 'pessoa', 'usuario', 'pontoAcesso']),
                        duplicado: false,
                    );
                }),
            );
        } catch (LockTimeoutException) {
            throw new DomainException('Já existe um reconhecimento desta placa em processamento. Tente novamente.');
        }
    }

    private function resolverStatus(?Veiculo $veiculo, bool $liberarManualmente): StatusAcesso
    {
        if ($liberarManualmente) {
            return StatusAcesso::LiberadoManualmente;
        }

        return match (true) {
            $veiculo === null => StatusAcesso::NaoCadastrado,
            $veiculo->estaAutorizado() => StatusAcesso::Autorizado,
            default => StatusAcesso::Bloqueado,
        };
    }

    /** @return array{0: TipoAcesso, 1: bool} */
    private function resolverTipoEDuplicidade(
        ?Acesso $ultimo,
        ?PontoAcesso $pontoAcesso,
        ?TipoAcesso $tipoSolicitado,
        bool $aplicarDuplicidade,
    ): array {
        $tipoForcado = $this->tipoForcadoPeloPonto($pontoAcesso, $tipoSolicitado);
        $recente = $aplicarDuplicidade && $this->acessoEstaNaJanelaDeDuplicidade($ultimo);

        if ($tipoForcado !== null) {
            return [
                $tipoForcado,
                $recente && $ultimo?->tipo === $tipoForcado,
            ];
        }

        if ($recente && $ultimo !== null) {
            return [$ultimo->tipo, true];
        }

        return [
            $ultimo?->tipo === TipoAcesso::Entrada
                ? TipoAcesso::Saida
                : TipoAcesso::Entrada,
            false,
        ];
    }

    private function tipoForcadoPeloPonto(
        ?PontoAcesso $pontoAcesso,
        ?TipoAcesso $tipoSolicitado,
    ): ?TipoAcesso {
        $sentido = $pontoAcesso?->sentido;

        if ($sentido === SentidoPontoAcesso::Entrada) {
            if ($tipoSolicitado !== null && $tipoSolicitado !== TipoAcesso::Entrada) {
                throw new DomainException('O ponto de acesso selecionado aceita apenas entradas.');
            }

            return TipoAcesso::Entrada;
        }

        if ($sentido === SentidoPontoAcesso::Saida) {
            if ($tipoSolicitado !== null && $tipoSolicitado !== TipoAcesso::Saida) {
                throw new DomainException('O ponto de acesso selecionado aceita apenas saídas.');
            }

            return TipoAcesso::Saida;
        }

        return $tipoSolicitado;
    }

    private function acessoEstaNaJanelaDeDuplicidade(?Acesso $acesso): bool
    {
        if ($acesso?->data_hora === null) {
            return false;
        }

        $segundos = max(0, (int) config('guarita.duplicate_window_seconds', 20));

        return $segundos > 0 && $acesso->data_hora->gte(now()->subSeconds($segundos));
    }

    private function validarPontoAcesso(?PontoAcesso $pontoAcesso): void
    {
        if ($pontoAcesso !== null && ($pontoAcesso->trashed() || ! $pontoAcesso->ativo)) {
            throw new DomainException('O ponto de acesso selecionado está inativo.');
        }
    }
}
