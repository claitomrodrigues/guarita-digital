<?php

namespace App\Models;

use App\Enums\OrigemAcesso;
use App\Enums\StatusAcesso;
use App\Enums\TipoAcesso;
use App\Support\Placa;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acesso extends Model
{
    use HasFactory;

    protected $fillable = [
        'veiculo_id',
        'pessoa_id',
        'user_id',
        'ponto_acesso_id',
        'placa_reconhecida',
        'tipo',
        'status',
        'origem',
        'data_hora',
        'imagem',
        'confianca',
        'observacoes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoAcesso::class,
            'status' => StatusAcesso::class,
            'origem' => OrigemAcesso::class,
            'data_hora' => 'datetime',
            'confianca' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function setPlacaReconhecidaAttribute(string $valor): void
    {
        $this->attributes['placa_reconhecida'] = Placa::normalizar($valor);
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class)->withTrashed();
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class)->withTrashed();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function pontoAcesso(): BelongsTo
    {
        return $this->belongsTo(PontoAcesso::class)->withTrashed();
    }

    public function scopeDaPlaca(Builder $query, string $placa): Builder
    {
        return $query->where('placa_reconhecida', Placa::normalizar($placa));
    }

    public function scopeNoPeriodo(
        Builder $query,
        CarbonInterface|string|null $inicio,
        CarbonInterface|string|null $fim,
    ): Builder {
        return $query
            ->when($inicio, static fn (Builder $query, mixed $valor): Builder => $query->where('data_hora', '>=', $valor))
            ->when($fim, static fn (Builder $query, mixed $valor): Builder => $query->where('data_hora', '<=', $valor));
    }

    public function permitePassagem(): bool
    {
        return $this->status?->permitePassagem() ?? false;
    }
}
