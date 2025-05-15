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
        Schema::create('empenhos', function (Blueprint $table) {
            // Coluna ID auto-incremental (BIGINT UNSIGNED) - Padrão Laravel.
            // Corresponde ao 'id integer NOT NULL' do seu dump, mas usando o padrão Laravel.
            $table->id();
            $table->string('empenho')->nullable(); // character varying(255)
            $table->string('liquidacao', 50)->nullable(); // character varying(50)
            $table->integer('ano')->nullable(); // integer
            $table->string('tipo', 50)->nullable(); // character varying(50)
            $table->string('ficha', 50)->nullable(); // character varying(50)
            $table->string('numero_unidade', 50)->nullable(); // character varying(50)
            $table->string('unidade')->nullable(); // character varying(255)
            $table->string('numero_subunidade', 50)->nullable(); // character varying(50)
            $table->string('subunidade')->nullable(); // character varying(255)
            $table->string('numero_fonte_recurso', 50)->nullable(); // character varying(50)
            $table->string('fonte_recurso')->nullable(); // character varying(255)
            $table->string('numero_fornecedor', 50)->nullable(); // character varying(50)
            $table->string('fornecedor')->nullable(); // character varying(255)
            $table->string('numero_modalidade', 50)->nullable(); // character varying(50)
            $table->string('modalidade')->nullable(); // character varying(255)
            $table->string('licitacao')->nullable(); // character varying(255)
            $table->string('ano_licitacao', 50)->nullable(); // character varying(50)
            $table->string('processo_compra', 100)->nullable(); // character varying(100)
            $table->string('banco', 50)->nullable(); // character varying(50)
            $table->string('conta', 50)->nullable(); // character varying(50)
            $table->string('modelo', 50)->nullable(); // character varying(50)
            $table->text('caminho')->nullable(); // Para o caminho do arquivo

            // Adiciona as colunas created_at e updated_at
            $table->timestamps();

            // Adiciona a coluna deleted_at para soft deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empenhos');
    }
};
