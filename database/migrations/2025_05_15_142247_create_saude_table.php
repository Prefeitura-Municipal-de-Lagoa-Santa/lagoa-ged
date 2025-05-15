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
        Schema::create('saude', function (Blueprint $table) {
            $table->id();
            $table->integer('atendimento')->nullable();
            $table->string('nome')->nullable();
            $table->string('secretaria')->nullable();
            $table->string('tipo')->nullable();
            $table->date('data')->nullable();
            $table->text('caminho')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saude');
    }
};
