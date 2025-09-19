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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OptimizedImportDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $tempPath;
    public $readGroupIds;
    public $writeGroupIds;
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1800; // 30 minutos
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;
    
    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    private $chunkSize = 100; // Processar em chunks de 100 registros
    private $maxFileSize = 10485760; // 10MB - se maior que isso, usar chunks paralelos

    public function __construct($user, $tempPath, $readGroupIds, $writeGroupIds)
    {
        $this->user = $user;
        $this->tempPath = $tempPath;
        $this->readGroupIds = $readGroupIds;
        $this->writeGroupIds = $writeGroupIds;
    }

    public function handle()
    {
        $startTime = microtime(true);
        $user = $this->user;
        $filePath = Storage::path($this->tempPath);
        
        // Verificar tamanho do arquivo
        $fileSize = filesize($filePath);
        
        if ($fileSize > $this->maxFileSize) {
            Log::info("CSV Import Job: Arquivo grande detectado, processando em chunks paralelos", [
                'file_size' => $fileSize,
                'max_size' => $this->maxFileSize
            ]);
            
            return $this->processLargeFileInChunks($filePath, $user);
        }
        
        return $this->processFileDirectly($filePath, $user, $startTime);
    }

    /**
     * Processar arquivo grande em chunks paralelos
     */
    private function processLargeFileInChunks($filePath, $user)
    {
        $jobId = uniqid('import_');
        $chunks = [];
        $currentChunk = [];
        $header = [];
        $firstRow = true;
        
        try {
            $splFile = new \SplFileObject($filePath, 'r');
            $splFile->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);

            foreach ($splFile as $row) {
                if ($firstRow) {
                    $header = array_map('trim', $row);
                    $firstRow = false;
                    continue;
                }

                if (empty(array_filter($row))) {
                    continue;
                }

                if (count($header) !== count($row)) {
                    Log::warning("CSV Import Job: Linha ignorada - colunas inconsistentes", ['row' => $row]);
                    continue;
                }

                $data = array_combine($header, array_map('trim', $row));
                $currentChunk[] = $data;

                if (count($currentChunk) >= $this->chunkSize) {
                    $chunks[] = $currentChunk;
                    $currentChunk = [];
                }
            }

            // Adicionar último chunk se não estiver vazio
            if (!empty($currentChunk)) {
                $chunks[] = $currentChunk;
            }

            // Disparar jobs para cada chunk
            foreach ($chunks as $index => $chunkData) {
                ProcessDocumentChunkJob::dispatch(
                    $user,
                    $chunkData,
                    $this->readGroupIds,
                    $this->writeGroupIds,
                    $jobId
                )->onQueue('documents');
                
                Log::info("CSV Import Job: Chunk {$index} despachado", [
                    'job_id' => $jobId,
                    'chunk_size' => count($chunkData)
                ]);
            }

            Storage::delete($this->tempPath);

            Log::info("CSV Import Job: Processamento em chunks iniciado", [
                'job_id' => $jobId,
                'total_chunks' => count($chunks),
                'total_rows' => array_sum(array_map('count', $chunks))
            ]);

            // Enviar notificação de início
            $this->sendChunkProcessingNotification($jobId, count($chunks));

        } catch (\Exception $e) {
            Log::error("CSV Import Job: Erro ao processar arquivo em chunks - " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->sendErrorNotification($e->getMessage(), 0);
        }
    }

    /**
     * Processar arquivo diretamente (arquivos menores)
     */
    private function processFileDirectly($filePath, $user, $startTime)
    {
        $readGroupIds = collect(array_filter($this->readGroupIds))
            ->map(fn($id) => $this->makeObjectId($id))
            ->toArray();

        $writeGroupIds = collect(array_filter($this->writeGroupIds))
            ->map(fn($id) => $this->makeObjectId($id))
            ->toArray();

        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $processedInBatch = 0;

        try {
            $splFile = new \SplFileObject($filePath, 'r');
            $splFile->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);

            $header = [];
            $firstRow = true;
            $batch = [];

            foreach ($splFile as $row) {
                if ($firstRow) {
                    $header = array_map('trim', $row);
                    $firstRow = false;
                    continue;
                }

                if (empty(array_filter($row))) {
                    continue;
                }

                if (count($header) !== count($row)) {
                    $errors[] = "Linha ignorada: colunas inconsistentes - " . json_encode($row);
                    Log::warning("CSV Import Job: Colunas inconsistentes.", ['row' => $row]);
                    $skippedCount++;
                    continue;
                }

                $data = array_combine($header, array_map('trim', $row));

                $filename = $data['filename'] ?? null;
                $fileLocationPath = $data['file_location_path'] ?? null;

                if (!$filename || !$fileLocationPath) {
                    $errors[] = "Linha ignorada: falta filename ou file_location_path - " . json_encode($data);
                    Log::warning("CSV Import Job: Campos obrigatórios ausentes.", ['data' => $data]);
                    $skippedCount++;
                    continue;
                }

                // Verificar duplicatas
                $existingDocument = Document::where('filename', $filename)
                    ->where('file_location.path', $fileLocationPath)
                    ->first();

                if ($existingDocument) {
                    $errors[] = "Documento duplicado ignorado: {$filename} em {$fileLocationPath}";
                    Log::info("CSV Import Job: Documento duplicado.", ['filename' => $filename]);
                    $skippedCount++;
                    continue;
                }

                // Preparar dados do documento
                $documentData = $this->prepareDocumentData($data, $user, $readGroupIds, $writeGroupIds);
                $batch[] = $documentData;

                // Processar em lotes menores
                if (count($batch) >= 25) { // Lotes menores para evitar timeout
                    $batchResult = $this->processBatch($batch);
                    $importedCount += $batchResult['imported'];
                    $skippedCount += $batchResult['skipped'];
                    $errors = array_merge($errors, $batchResult['errors']);
                    
                    $batch = [];
                    
                    // Log de progresso
                    Log::info("CSV Import Job: Lote processado. Total importados: {$importedCount}, ignorados: {$skippedCount}");
                }
            }

            // Processar lote restante
            if (!empty($batch)) {
                $batchResult = $this->processBatch($batch);
                $importedCount += $batchResult['imported'];
                $skippedCount += $batchResult['skipped'];
                $errors = array_merge($errors, $batchResult['errors']);
            }

            Storage::delete($this->tempPath);

            $duration = round(microtime(true) - $startTime, 2);
            
            Log::info("CSV Import Job: Finalizado. Importados: {$importedCount}, Ignorados: {$skippedCount}");

            // Enviar notificação de conclusão
            $this->sendCompletionNotification($importedCount, $skippedCount, $duration);

        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            Log::error("CSV Import Job: Erro inesperado - " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->sendErrorNotification($e->getMessage(), $duration);
        }
    }

    // Incluir todos os métodos auxiliares do job original
    private function prepareDocumentData($data, $user, $readGroupIds, $writeGroupIds)
    {
        // Método igual ao job original
        $documentData = [
            'title' => $data['title'] ?? null,
            'filename' => $data['filename'],
            'file_extension' => $data['file_extension'] ?? null,
            'mime_type' => $data['mime_type'] ?? null,
            'upload_date' => isset($data['upload_date']) ? $this->toMongoDate($data['upload_date']) : $this->toMongoDate(\Carbon\Carbon::now()),
            'uploaded_by' => $this->makeObjectId($user->id),
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

    private function processBatch($batch)
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($batch as $documentData) {
            try {
                Document::create($documentData);
                $imported++;
                Log::info("CSV Import Job: Documento importado - {$documentData['filename']}");
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = "Erro ao importar {$documentData['filename']}: " . $e->getMessage();
                Log::error("CSV Import Job: Erro ao importar documento", [
                    'filename' => $documentData['filename'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * Cria um ObjectId dinamicamente (fallback: string)
     */
    private function makeObjectId($id)
    {
        $class = '\\MongoDB\\BSON\\ObjectId';
        if (class_exists($class)) {
            return new $class($id);
        }
        return $id;
    }

    /**
     * Converte valores para MongoDB\\BSON\\UTCDateTime em UTC (ms epoch)
     */
    private function toMongoDate($value)
    {
        if ($value instanceof \DateTimeInterface) {
            $dt = Carbon::instance($value)->utc();
        } elseif (is_string($value)) {
            $dt = Carbon::parse($value)->utc();
        } else {
            $dt = Carbon::now()->utc();
        }
        $utcClass = '\\MongoDB\\BSON\\UTCDateTime';
        if (class_exists($utcClass)) {
            $ms = (int) ($dt->getTimestamp() * 1000);
            return new $utcClass($ms);
        }
        return $dt;
    }

    private function sendCompletionNotification($importedCount, $skippedCount, $duration)
    {
        try {
            $notificationService = new \App\Services\EnhancedNotificationService();
            $notification = $notificationService->jobCompleted(
                $this->user->id,
                'Importação de Documentos',
                $importedCount,
                $skippedCount,
                (float) $duration,
                [
                    'file_path' => $this->tempPath,
                    'total_processed' => $importedCount + $skippedCount,
                    'imported' => $importedCount,
                    'skipped' => $skippedCount
                ]
            );
            
            Log::info("Notificação de conclusão enviada", [
                'user_id' => $this->user->id,
                'notification_id' => $notification ? $notification->id : null
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao enviar notificação de conclusão", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendChunkProcessingNotification($jobId, $totalChunks)
    {
        try {
            $notificationService = new \App\Services\EnhancedNotificationService();
            $notification = $notificationService->create(
                $this->user->id,
                'Processamento de Importação Iniciado',
                "Arquivo grande detectado. Processando em {$totalChunks} partes para melhor performance.",
                'info',
                'import',
                [
                    'job_id' => $jobId,
                    'total_chunks' => $totalChunks,
                    'file_path' => $this->tempPath
                ]
            );
            
            Log::info("Notificação de processamento em chunks enviada", [
                'user_id' => $this->user->id,
                'job_id' => $jobId
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao enviar notificação de chunks", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendErrorNotification($errorMessage, $duration)
    {
        try {
            $notificationService = new \App\Services\EnhancedNotificationService();
            $notification = $notificationService->create(
                $this->user->id,
                'Erro na Importação de Documentos',
                "Ocorreu um erro durante a importação: {$errorMessage}",
                'error',
                'import',
                [
                    'file_path' => $this->tempPath,
                    'error_message' => $errorMessage,
                    'duration' => $duration
                ]
            );
            
            Log::info("Notificação de erro enviada", [
                'user_id' => $this->user->id,
                'notification_id' => $notification ? $notification->id : null
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao enviar notificação de erro", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
