<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acesso_id')->unique()->constrained('acessos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pendente')->index();
            $table->string('nome_visitante', 150)->nullable();
            $table->text('documento_visitante')->nullable();
            $table->string('destino', 180)->nullable();
            $table->text('motivo_visita')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamp('iniciada_em')->useCurrent();
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();

            $table->index(['status', 'iniciada_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triagens');
    }
};
