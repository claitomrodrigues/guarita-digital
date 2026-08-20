<?php

namespace App\Models;

use App\Enums\PerfilUsuario;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'perfil',
        'ativo',
        'ultimo_login_em',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'perfil' => PerfilUsuario::class,
            'ativo' => 'boolean',
            'ultimo_login_em' => 'datetime',
        ];
    }

    public function setNameAttribute(string $valor): void
    {
        $this->attributes['name'] = trim($valor);
    }

    public function setEmailAttribute(string $valor): void
    {
        $this->attributes['email'] = mb_strtolower(trim($valor), 'UTF-8');
    }

    public function acessos(): HasMany
    {
        return $this->hasMany(Acesso::class);
    }

    public function logsAuditoria(): HasMany
    {
        return $this->hasMany(LogAuditoria::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeDoPerfil(Builder $query, PerfilUsuario|string $perfil): Builder
    {
        return $query->where('perfil', $perfil instanceof PerfilUsuario ? $perfil->value : $perfil);
    }

    public function possuiPerfil(PerfilUsuario|string ...$perfis): bool
    {
        $perfilAtual = $this->perfil instanceof PerfilUsuario
            ? $this->perfil->value
            : (string) $this->perfil;

        foreach ($perfis as $perfil) {
            $valor = $perfil instanceof PerfilUsuario ? $perfil->value : $perfil;

            if (hash_equals($perfilAtual, $valor)) {
                return true;
            }
        }

        return false;
    }

    public function isAdministrador(): bool
    {
        return $this->possuiPerfil(PerfilUsuario::Administrador);
    }

    public function isSeguranca(): bool
    {
        return $this->possuiPerfil(PerfilUsuario::Seguranca);
    }
}
