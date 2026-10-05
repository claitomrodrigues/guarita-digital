<?php

namespace App\Services;

use App\Enums\StatusAcesso;
use App\Enums\StatusTriagem;
use App\Models\Acesso;
use App\Models\Triagem;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class TriagemService
{
    public function __construct(private readonly AuditoriaService $auditoria)
    {
    }

    public function criarSeNecessaria(Acesso $acesso): ?Triagem
    {
        if (! in_array($acesso->status, [StatusAcesso::NaoCadastrado, StatusAcesso::Bloqueado, StatusAcesso::LeituraInconclusiva], true)) {
            return null;
        }

        return Triagem::query()->firstOrCreate(
            ['acesso_id' => $acesso->id],
            ['status' => StatusTriagem::Pendente, 'iniciada_em' => now()],
        );
    }

    public function concluir(Triagem $triagem, User $usuario, array $dados): Triagem
    {
        return DB::transaction(function () use ($triagem, $usuario, $dados): Triagem {
            $triagem = Triagem::query()->lockForUpdate()->findOrFail($triagem->id);

            if ($triagem->status !== StatusTriagem::Pendente) {
                throw new DomainException('Esta triagem já foi concluída.');
            }

            $decisao = StatusTriagem::from($dados['decisao']);
            $triagem->update([
                'nome_visitante' => $dados['nome_visitante'],
                'documento_visitante' => $dados['documento_visitante'] ?? null,
                'destino' => $dados['destino'],
                'motivo_visita' => $dados['motivo_visita'],
                'observacoes' => $dados['observacoes'] ?? null,
                'status' => $decisao,
                'user_id' => $usuario->id,
                'concluida_em' => now(),
            ]);

            if ($decisao === StatusTriagem::Autorizada) {
                $triagem->acesso()->update([
                    'status' => StatusAcesso::LiberadoManualmente,
                    'user_id' => $usuario->id,
                ]);
            }

            $this->auditoria->registrar(
                acao: 'conclusao_triagem',
                entidade: $triagem,
                dadosAnteriores: ['status' => StatusTriagem::Pendente->value],
                dadosNovos: ['status' => $decisao->value],
                usuario: $usuario,
            );

            return $triagem->refresh()->load(['acesso.veiculo.pessoa', 'acesso.pontoAcesso', 'usuario']);
        });
    }
}
