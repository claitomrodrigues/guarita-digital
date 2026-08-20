<?php

namespace App\Observers;

use App\Services\AuditoriaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditoriaObserver
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
    ) {
    }

    public function created(Model $model): void
    {
        $this->auditoria->registrar(
            acao: 'criado',
            entidade: $model,
            dadosNovos: $model->getAttributes(),
        );
    }

    public function updated(Model $model): void
    {
        $alterados = array_values(array_diff(array_keys($model->getChanges()), ['updated_at']));

        if ($alterados === []) {
            return;
        }

        $this->auditoria->registrar(
            acao: 'atualizado',
            entidade: $model,
            dadosAnteriores: Arr::only($model->getOriginal(), $alterados),
            dadosNovos: Arr::only($model->getAttributes(), $alterados),
        );
    }

    public function deleted(Model $model): void
    {
        $this->auditoria->registrar(
            acao: 'excluido',
            entidade: $model,
            dadosAnteriores: $model->getOriginal(),
        );
    }

    public function restored(Model $model): void
    {
        $this->auditoria->registrar(
            acao: 'restaurado',
            entidade: $model,
            dadosNovos: $model->getAttributes(),
        );
    }
}
