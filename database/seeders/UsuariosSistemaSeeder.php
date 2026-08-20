<?php

namespace Database\Seeders;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsuariosSistemaSeeder extends Seeder
{
    public function run(): void
    {
        User::withTrashed()->updateOrCreate(
            ['perfil' => PerfilUsuario::Administrador],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'email' => env('ADMIN_EMAIL', 'admin@guarita.local'),
                'password' => env('ADMIN_PASSWORD', 'Guarita@2026'),
                'ativo' => true,
                'email_verified_at' => now(),
                'deleted_at' => null,
            ],
        );

        User::withTrashed()->updateOrCreate(
            ['perfil' => PerfilUsuario::Seguranca],
            [
                'name' => env('SEGURANCA_NAME', 'Segurança da Guarita'),
                'email' => env('SEGURANCA_EMAIL', 'seguranca@guarita.local'),
                'password' => env('SEGURANCA_PASSWORD', 'Seguranca@2026'),
                'ativo' => true,
                'email_verified_at' => now(),
                'deleted_at' => null,
            ],
        );
    }
}
