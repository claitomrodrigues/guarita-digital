<?php

namespace App\Models;

use App\Enums\PerfilUsuario;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'matricula',
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

    public function setMatriculaAttribute(?string $valor): void
    {
        $valor = $valor !== null ? trim($valor) : null;
        $this->attributes['matricula'] = $valor !== '' ? $valor : null;
    }

    public function isAdministrador(): bool
    {
        return $this->perfil === PerfilUsuario::Administrador;
    }

    public function isSeguranca(): bool
    {
        return $this->perfil === PerfilUsuario::Seguranca;
    }
}
