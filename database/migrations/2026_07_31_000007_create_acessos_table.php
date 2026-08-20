<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acessos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veiculo_id')->nullable()->constrained('veiculos')->nullOnDelete();
            $table->foreignId('pessoa_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ponto_acesso_id')->nullable()->constrained('pontos_acesso')->nullOnDelete();
            $table->string('placa_reconhecida', 7)->index();
            $table->string('tipo', 20)->index();
            $table->string('status', 40)->index();
            $table->string('origem', 20)->default('ocr')->index();
            $table->dateTime('data_hora')->index();
            $table->string('imagem', 500)->nullable();
            $table->decimal('confianca', 5, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['placa_reconhecida', 'data_hora']);
            $table->index(['status', 'data_hora']);
            $table->index(['tipo', 'data_hora']);
            $table->index(['veiculo_id', 'data_hora']);
            $table->index(['ponto_acesso_id', 'data_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acessos');
    }
};
