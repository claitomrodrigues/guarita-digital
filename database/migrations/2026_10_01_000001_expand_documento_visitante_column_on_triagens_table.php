<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('triagens', function (Blueprint $table) {
            $table->text('documento_visitante')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('triagens', function (Blueprint $table) {
            $table->string('documento_visitante', 512)->nullable()->change();
        });
    }
};