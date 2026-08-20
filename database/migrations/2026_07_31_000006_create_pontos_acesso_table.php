<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pontos_acesso', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('codigo', 40)->unique();
            $table->string('sentido', 20)->default('ambos')->index();
            $table->string('localizacao', 150)->nullable();
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pontos_acesso');
    }
};
