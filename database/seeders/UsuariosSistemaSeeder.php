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
            ['email' => env('ADMIN_EMAIL', 'admin@guarita.local')],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'matricula' => env('ADMIN_MATRICULA', 'admin'),
                'perfil' => PerfilUsuario::Administrador,
                'password' => env('ADMIN_PASSWORD', 'Guarita@2026'),
                'ativo' => true,
                'email_verified_at' => now(),
                'deleted_at' => null,
            ],
        );
    }
}
