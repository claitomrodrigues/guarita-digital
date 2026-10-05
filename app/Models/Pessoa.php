<?php

namespace App\Models;

use App\Enums\TipoVinculo;
use App\Support\Cpf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pessoa extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nome',
        'cpf',
        'matricula',
        'email',
        'telefone',
        'tipo_vinculo',
        'ativo',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'tipo_vinculo' => TipoVinculo::class,
            'ativo' => 'boolean',
        ];
    }

    public function setNomeAttribute(string $valor): void
    {
        $this->attributes['nome'] = trim($valor);
    }

    public function setCpfAttribute(?string $valor): void
    {
        $this->attributes['cpf'] = Cpf::normalizar($valor);
    }

    public function setMatriculaAttribute(?string $valor): void
    {
        $matricula = $valor !== null ? trim($valor) : null;
        $this->attributes['matricula'] = $matricula !== '' ? $matricula : null;
    }

    public function setEmailAttribute(?string $valor): void
    {
        $email = $valor !== null ? mb_strtolower(trim($valor), 'UTF-8') : null;
        $this->attributes['email'] = $email !== '' ? $email : null;
    }

    public function setTelefoneAttribute(?string $valor): void
    {
        $telefone = $valor !== null ? trim($valor) : null;
        $this->attributes['telefone'] = $telefone !== '' ? $telefone : null;
    }

    public function veiculos(): HasMany
    {
        return $this->hasMany(Veiculo::class);
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
