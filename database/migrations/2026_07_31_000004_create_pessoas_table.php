<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pessoas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('cpf', 11)->nullable()->unique();
            $table->string('matricula', 40)->nullable()->unique();
            $table->string('email', 150)->nullable()->index();
            $table->string('telefone', 20)->nullable();
            $table->string('tipo_vinculo', 30)->index();
            $table->boolean('ativo')->default(true)->index();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['nome', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pessoas');
    }
};
