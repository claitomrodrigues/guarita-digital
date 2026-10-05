<?php

namespace App\Models;

use App\Enums\SentidoPontoAcesso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PontoAcesso extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pontos_acesso';

    protected $fillable = [
        'nome',
        'codigo',
        'sentido',
        'localizacao',
        'descricao',
        'ativo',
        'camera_token_hash',
        'ultima_comunicacao_em',
        'versao_camera',
    ];

    protected $hidden = ['camera_token_hash'];

    protected function casts(): array
    {
        return [
            'sentido' => SentidoPontoAcesso::class,
            'ativo' => 'boolean',
            'ultima_comunicacao_em' => 'datetime',
        ];
    }

    public function setNomeAttribute(string $valor): void
    {
        $this->attributes['nome'] = trim($valor);
    }

    public function setCodigoAttribute(string $valor): void
    {
        $this->attributes['codigo'] = mb_strtoupper(trim($valor), 'UTF-8');
    }

    public function acessos(): HasMany
    {
        return $this->hasMany(Acesso::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function definirTokenCamera(string $token): void
    {
        $this->forceFill(['camera_token_hash' => hash('sha256', $token)])->save();
    }
}
