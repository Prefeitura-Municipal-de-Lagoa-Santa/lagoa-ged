<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;

class EnsureDocumentIndexes extends Command
{
    protected $signature = 'documents:ensure-indexes';
    protected $description = 'Cria índices na coleção de documentos para melhorar performance de import e busca.';

    public function handle()
    {
        $this->info('Criando índices na coleção de documentos...');

        Document::raw(function ($collection) {
            // Índice composto para evitar duplicatas e acelerar busca por filename + path
            $collection->createIndex([
                'filename' => 1,
                'file_location.path' => 1,
            ], [
                'name' => 'uniq_filename_path',
                'unique' => false, // deixar false para não falhar em dados antigos; validação de duplicidade é feita na aplicação
                'background' => true,
            ]);

            // Índices auxiliares
            $collection->createIndex(['created_at' => -1], ['background' => true]);
            $collection->createIndex(['metadata.document_year' => 1], ['background' => true]);
            $collection->createIndex(['tags' => 1], ['background' => true]);
        });

        $this->info('Índices criados (ou já existentes).');
        return 0;
    }
}
