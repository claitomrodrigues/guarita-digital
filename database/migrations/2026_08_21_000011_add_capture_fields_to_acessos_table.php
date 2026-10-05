<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acessos', function (Blueprint $table) {
            $table->string('capture_id', 120)->nullable()->unique()->after('id');
            $table->decimal('confianca_yolo', 5, 4)->nullable()->after('confianca');
            $table->unsignedTinyInteger('quadros_confirmados')->nullable()->after('confianca_yolo');
            $table->string('modelo_placa', 20)->nullable()->after('quadros_confirmados');
        });
    }

    public function down(): void
    {
        Schema::table('acessos', function (Blueprint $table) {
            $table->dropUnique(['capture_id']);
            $table->dropColumn(['capture_id', 'confianca_yolo', 'quadros_confirmados', 'modelo_placa']);
        });
    }
};
