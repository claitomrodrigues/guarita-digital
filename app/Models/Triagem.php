<?php

namespace App\Models;

use App\Enums\StatusTriagem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Triagem extends Model
{
    use HasFactory;

    protected $table = 'triagens';

    protected $fillable = [
        'acesso_id', 'user_id', 'status', 'nome_visitante', 'documento_visitante',
        'destino', 'motivo_visita', 'observacoes', 'iniciada_em', 'concluida_em',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusTriagem::class,
            'documento_visitante' => 'encrypted',
            'iniciada_em' => 'datetime',
            'concluida_em' => 'datetime',
        ];
    }

    public function acesso(): BelongsTo
    {
        return $this->belongsTo(Acesso::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', StatusTriagem::Pendente);
    }
}
