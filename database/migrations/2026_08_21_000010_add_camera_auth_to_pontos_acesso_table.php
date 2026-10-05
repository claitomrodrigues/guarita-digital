<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pontos_acesso', function (Blueprint $table) {
            $table->string('camera_token_hash', 64)->nullable()->unique()->after('ativo');
            $table->timestamp('ultima_comunicacao_em')->nullable()->after('camera_token_hash');
            $table->string('versao_camera', 40)->nullable()->after('ultima_comunicacao_em');
        });
    }

    public function down(): void
    {
        Schema::table('pontos_acesso', function (Blueprint $table) {
            $table->dropUnique(['camera_token_hash']);
            $table->dropColumn(['camera_token_hash', 'ultima_comunicacao_em', 'versao_camera']);
        });
    }
};
