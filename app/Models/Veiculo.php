<?php

namespace App\Models;

use App\Enums\TipoVeiculo;
use App\Support\Placa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Veiculo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pessoa_id',
        'placa',
        'marca',
        'modelo',
        'cor',
        'tipo',
        'ano',
        'ativo',
        'autorizado',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoVeiculo::class,
            'ano' => 'integer',
            'ativo' => 'boolean',
            'autorizado' => 'boolean',
        ];
    }

    public function setPlacaAttribute(string $valor): void
    {
        $this->attributes['placa'] = Placa::normalizar($valor);
    }

    public function setMarcaAttribute(?string $valor): void
    {
        $this->attributes['marca'] = $this->limparTextoOpcional($valor);
    }

    public function setModeloAttribute(?string $valor): void
    {
        $this->attributes['modelo'] = $this->limparTextoOpcional($valor);
    }

    public function setCorAttribute(?string $valor): void
    {
        $this->attributes['cor'] = $this->limparTextoOpcional($valor);
    }

    public static function normalizarPlaca(string $placa): string
    {
        return Placa::normalizar($placa);
    }

    public static function placaValida(string $placa): bool
    {
        return Placa::valida($placa);
    }

    public static function formatarPlaca(string $placa): string
    {
        return Placa::formatar($placa);
    }

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeAutorizados(Builder $query): Builder
    {
        return $query->where('autorizado', true);
    }

    public function estaAutorizado(): bool
    {
        return ! $this->trashed()
            && $this->ativo
            && $this->autorizado
            && (bool) $this->pessoa?->ativo;
    }

    private function limparTextoOpcional(?string $valor): ?string
    {
        $valor = $valor !== null ? trim($valor) : null;

        return $valor !== '' ? $valor : null;
    }
}
