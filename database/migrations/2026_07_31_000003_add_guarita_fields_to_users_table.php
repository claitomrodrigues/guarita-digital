<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('perfil', ['administrador', 'seguranca'])
                ->default('seguranca')
                ->unique()
                ->after('password');

            $table->boolean('ativo')->default(true)->index()->after('perfil');
            $table->timestamp('ultimo_login_em')->nullable()->after('ativo');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['perfil']);
            $table->dropIndex(['ativo']);
            $table->dropColumn(['perfil', 'ativo', 'ultimo_login_em', 'deleted_at']);
        });
    }
};
