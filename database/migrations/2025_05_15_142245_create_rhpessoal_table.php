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
        // Nota: O nome da tabela no dump é 'rhpessoal' (singular).
        // Laravel por padrão esperaria 'rhpessoals' (plural) para um Model RHPessoal.
        // Certifique-se de definir protected $table = 'rhpessoal'; no seu Model RHPessoal.
        Schema::create('rhpessoal', function (Blueprint $table) {
            $table->id(); // Coluna ID auto-incremental (BIGINT UNSIGNED) - Padrão Laravel
            // Adicione as colunas da sua tabela 'rhpessoal' com base no arquivo.sql
            // Exemplo (ajuste conforme o seu dump):
            $table->string('matricula')->nullable()->unique(); // Exemplo de coluna única
            $table->string('nome')->nullable();
            $table->string('situacao')->nullable();
            $table->text('caminho')->nullable(); // Para o caminho do arquivo escaneado (ficha do funcionário, etc.)
            // Adicione outras colunas específicas da tabela 'rhpessoal'

            $table->timestamps(); // Adiciona as colunas created_at e updated_at
            $table->softDeletes(); // Adiciona a coluna deleted_at para soft deletes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rhpessoal');
    }
};
