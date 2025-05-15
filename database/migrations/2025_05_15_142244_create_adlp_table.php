<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('adlp', function (Blueprint $table) {
            $table->id(); // Cria um ID auto-incremental (BIGINT UNSIGNED)
            $table->string('numero')->nullable();
            $table->integer('ano')->nullable();
            $table->string('tipo', 50)->nullable(); // Use string com limite se o original for varchar(50)
            $table->text('caminho')->nullable(); // Para o caminho do arquivo
            $table->timestamps(); // Adiciona created_at e updated_at
            $table->softDeletes(); // Adiciona deleted_at para soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adlp');
    }
};
