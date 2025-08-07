<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;
use MongoDB\BSON\ObjectId;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessDocumentChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $chunkData;
    public $readGroupIds;
    public $writeGroupIds;
    public $parentJobId;
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutos por chunk
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    public function __construct($user, $chunkData, $readGroupIds, $writeGroupIds, $parentJobId = null)
    {
        $this->user = $user;
        $this->chunkData = $chunkData;
        $this->readGroupIds = $readGroupIds;
        $this->writeGroupIds = $writeGroupIds;
        $this->parentJobId = $parentJobId;
    }

    public function handle()
    {
        $startTime = microtime(true);
        $user = $this->user;

        $readGroupIds = collect(array_filter($this->readGroupIds))
            ->map(fn($id) => new ObjectId($id))
            ->toArray();

        $writeGroupIds = collect(array_filter($this->writeGroupIds))
            ->map(fn($id) => new ObjectId($id))
            ->toArray();

        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        try {
            foreach ($this->chunkData as $data) {
                $filename = $data['filename'] ?? null;
                $fileLocationPath = $data['file_location_path'] ?? null;

                if (!$filename || !$fileLocationPath) {
                    $errors[] = "Linha ignorada: falta filename ou file_location_path - " . json_encode($data);
                    Log::warning("CSV Chunk Job: Campos obrigatórios ausentes.", ['data' => $data]);
                    $skippedCount++;
                    continue;
                }

                // Verificar duplicatas
                $existingDocument = Document::where('filename', $filename)
                    ->where('file_location.path', $fileLocationPath)
                    ->first();

                if ($existingDocument) {
                    $errors[] = "Documento duplicado ignorado: {$filename} em {$fileLocationPath}";
                    Log::info("CSV Chunk Job: Documento duplicado.", ['filename' => $filename]);
                    $skippedCount++;
                    continue;
                }

                // Preparar dados do documento
                $documentData = $this->prepareDocumentData($data, $user, $readGroupIds, $writeGroupIds);

                try {
                    Document::create($documentData);
                    $importedCount++;
                    Log::info("CSV Chunk Job: Documento importado - {$filename}");
                } catch (\Exception $e) {
                    $errors[] = "Erro ao importar {$filename}: " . $e->getMessage();
                    Log::error("CSV Chunk Job: Erro ao importar documento", [
                        'filename' => $filename,
                        'error' => $e->getMessage()
                    ]);
                    $skippedCount++;
                }
            }

            $duration = round(microtime(true) - $startTime, 2);
            
            Log::info("CSV Chunk Job: Chunk finalizado", [
                'parent_job_id' => $this->parentJobId,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'duration' => $duration
            ]);

        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            Log::error("CSV Chunk Job: Erro inesperado - " . $e->getMessage(), [
                'parent_job_id' => $this->parentJobId,
                'trace' => $e->getTraceAsString(),
                'duration' => $duration
            ]);
            
            throw $e; // Re-throw para que o Laravel trate a falha
        }
    }

    /**
     * Preparar dados do documento
     */
    private function prepareDocumentData($data, $user, $readGroupIds, $writeGroupIds)
    {
        $documentData = [
            'title' => $data['title'] ?? null,
            'filename' => $data['filename'],
            'file_extension' => $data['file_extension'] ?? null,
            'mime_type' => $data['mime_type'] ?? null,
            'upload_date' => isset($data['upload_date']) ? \Carbon\Carbon::parse($data['upload_date']) : \Carbon\Carbon::now(),
            'uploaded_by' => new ObjectId($user->id),
            'status' => $data['status'] ?? 'active',
        ];

        $documentData['metadata'] = [
            'document_type' => $data['metadata_document_type'] ?? null,
            'document_year' => (int) ($data['metadata_document_year'] ?? 0),
        ];

        foreach ($data as $csvHeader => $value) {
            if (
                str_starts_with($csvHeader, 'metadata_') &&
                $csvHeader !== 'metadata_document_type' &&
                $csvHeader !== 'metadata_document_year'
            ) {
                $metadataFieldName = substr($csvHeader, strlen('metadata_'));
                $documentData['metadata'][$metadataFieldName] = $value;
            }
        }

        $documentData['tags'] = isset($data['tags']) && !empty($data['tags'])
            ? array_map('trim', explode('|', $data['tags']))
            : [];

        $documentType = strtoupper(trim($documentData['metadata']['document_type'] ?? ''));
        if (in_array($documentType, ['DECRETO', 'ATO', 'LEI', 'PORTARIA'])) {
            $documentData['tags'][] = 'ADLP';
        }

        $documentData['tags'] = array_unique(array_map(fn($tag) => strtoupper(trim($tag)), $documentData['tags']));

        $documentData['permissions'] = [
            'read_group_ids' => $readGroupIds,
            'write_group_ids' => $writeGroupIds,
        ];

        $documentData['file_location'] = [
            'path' => $data['file_location_path'],
            'storage_type' => $data['file_location_storage_type'] ?? 'file_server',
            'bucket_name' => $data['file_location_bucket_name'] ?? null,
        ];

        return $documentData;
    }
}
