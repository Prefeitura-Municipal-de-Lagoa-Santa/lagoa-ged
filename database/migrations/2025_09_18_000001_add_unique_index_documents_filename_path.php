<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // Usar comando raw para criar índice único composto
        \Illuminate\Support\Facades\DB::connection('mongodb')
            ->getMongoDB()
            ->selectCollection('documents')
            ->createIndex(
                ['filename' => 1, 'file_location.path' => 1],
                ['name' => 'uniq_documents_filename_path', 'unique' => true]
            );
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::connection('mongodb')
            ->getMongoDB()
            ->selectCollection('documents')
            ->dropIndex('uniq_documents_filename_path');
    }
};
