<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pessoa_id')->constrained('pessoas');
            $table->string('placa', 7)->unique();
            $table->string('marca', 80)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('cor', 50)->nullable();
            $table->string('tipo', 30)->default('carro')->index();
            $table->unsignedSmallInteger('ano')->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->boolean('autorizado')->default(true)->index();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pessoa_id', 'ativo']);
            $table->index(['placa', 'autorizado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculos');
    }
};
