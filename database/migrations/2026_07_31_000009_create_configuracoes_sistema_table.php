<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 100)->unique();
            $table->longText('valor')->nullable();
            $table->string('tipo', 20)->default('string');
            $table->string('grupo', 50)->default('geral')->index();
            $table->string('descricao', 255)->nullable();
            $table->boolean('publica')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes_sistema');
    }
};
