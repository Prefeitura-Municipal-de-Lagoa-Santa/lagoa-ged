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
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;

class ImportDocumentsJob implements ShouldQueue
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

        $readGroupIds = collect(array_filter($this->readGroupIds))
            ->map(fn($id) => new ObjectId($id))
            ->toArray();

        $writeGroupIds = collect(array_filter($this->writeGroupIds))
            ->map(fn($id) => new ObjectId($id))
            ->toArray();

    $importedCount = 0;
    $skippedCount = 0;
        $errors = [];
    // Aumentar o tamanho do lote para reduzir round-trips no banco (configurável via config/app.php IMPORT_BATCH_SIZE)
    $batchSize = (int) config('app.import_batch_size', 1000);
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

                // Preparar dados do documento
                $documentData = $this->prepareDocumentData($data, $user, $readGroupIds, $writeGroupIds);
                $batch[] = $documentData;
                $processedInBatch++;

                // Processar em lotes para evitar timeout
                if (count($batch) >= $batchSize) {
                    $batchResult = $this->processBatch($batch);
                    $importedCount += $batchResult['imported'];
                    $skippedCount += $batchResult['skipped'];
                    $errors = array_merge($errors, $batchResult['errors']);
                    
                    $batch = [];
                    $processedInBatch = 0;
                    
                    // Publicar progresso no Redis (opcional)
                    $this->publishProgress($importedCount, $skippedCount);
                    
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
                $this->publishProgress($importedCount, $skippedCount);
            }

            Storage::delete($this->tempPath);

            $duration = round(microtime(true) - $startTime, 2);
            
            Log::info("CSV Import Job: Finalizado. Importados: {$importedCount}, Ignorados: {$skippedCount}");
            if (!empty($errors)) {
                Log::warning("CSV Import Job: Erros encontrados", ['errors' => $errors]);
            }

            Log::info("=== CHAMANDO NOTIFICAÇÃO DE CONCLUSÃO ===", [
                'user_id' => $this->user->id,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'duration' => $duration
            ]);

            // Enviar notificação de conclusão
            $this->sendCompletionNotification($importedCount, $skippedCount, $duration);

        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            Log::error("CSV Import Job: Erro inesperado - " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            Log::info("=== CHAMANDO NOTIFICAÇÃO DE ERRO ===", [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'duration' => $duration
            ]);
            
            // Enviar notificação de erro
            $this->sendErrorNotification($e->getMessage(), $duration);
        }
    }

    /**
     * Preparar dados do documento
     */
    private function prepareDocumentData($data, $user, $readGroupIds, $writeGroupIds)
    {
        // Normalização de caminho para consistência (igual à view)
        $rawPath = $data['file_location_path'];
        $normalizedPath = $this->normalizePath($rawPath);

        $documentData = [
            'title' => $data['title'] ?? null,
            'filename' => $data['filename'],
            'file_extension' => $data['file_extension'] ?? null,
            'mime_type' => $data['mime_type'] ?? null,
            'upload_date' => isset($data['upload_date']) 
                ? new \MongoDB\BSON\UTCDateTime(\Carbon\Carbon::parse($data['upload_date'])->timestamp * 1000)
                : new \MongoDB\BSON\UTCDateTime(\Carbon\Carbon::now()->timestamp * 1000),
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
            'path' => $normalizedPath,
            'storage_type' => $data['file_location_storage_type'] ?? 'file_server',
            'bucket_name' => $data['file_location_bucket_name'] ?? null,
        ];

        // Timestamps manuais pois usaremos insertMany no bulk
        $now = now();
        $documentData['created_at'] = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);
        $documentData['updated_at'] = new \MongoDB\BSON\UTCDateTime($now->timestamp * 1000);

        return $documentData;
    }

    private function normalizePath(string $path): string
    {
        $p = str_replace('\\', '/', $path);
        $p = preg_replace('/\.PDF$/', '.pdf', $p);
        return $p;
    }

    private function makeKey(array $doc): string
    {
        $filename = strtoupper(trim($doc['filename'] ?? ''));
        $path = strtolower(trim($doc['file_location']['path'] ?? ''));
        return $filename . '|' . $path;
    }

    /**
     * Processar um lote de documentos com transação
     */
    private function processBatch($batch)
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        if (empty($batch)) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => []
            ];
        }

        // Deduplicar itens dentro do próprio lote por (filename, path)
        $mapByKey = [];
        foreach ($batch as $doc) {
            $key = $this->makeKey($doc);
            // Mantém o primeiro e descarta duplicatas subsequentes
            if (!isset($mapByKey[$key])) {
                $mapByKey[$key] = $doc;
            }
        }

        $uniqueBatch = array_values($mapByKey);

        // Consultar de uma vez documentos existentes para os pares do lote
        $pairs = array_map(function ($doc) {
            return [
                'filename' => $doc['filename'],
                'path' => $doc['file_location']['path']
            ];
        }, $uniqueBatch);

        $existingKeySet = [];
        try {
            // Usa $or com pares filename + path
            $filter = ['$or' => array_map(function ($p) {
                return [
                    'filename' => $p['filename'],
                    'file_location.path' => $p['path']
                ];
            }, $pairs)];

            // Evitar consulta inválida quando não há pares
            if (empty($filter['$or'])) {
                $filter = [];
            }

            Document::raw(function ($collection) use ($filter, &$existingKeySet) {
                $cursor = $collection->find($filter, [
                    'projection' => ['filename' => 1, 'file_location.path' => 1]
                ]);
                foreach ($cursor as $doc) {
                    $filename = isset($doc['filename']) ? strtoupper(trim($doc['filename'])) : '';
                    $path = isset($doc['file_location']['path']) ? strtolower(trim($doc['file_location']['path'])) : '';
                    $existingKeySet[$filename . '|' . $path] = true;
                }
            });
        } catch (\Exception $e) {
            Log::warning('CSV Import Job: Falha ao consultar duplicatas por lote. Prosseguindo sem filtro prévio.', [
                'error' => $e->getMessage()
            ]);
        }

        // Filtrar somente novos
        $toInsert = [];
        foreach ($uniqueBatch as $doc) {
            $key = $this->makeKey($doc);
            if (!isset($existingKeySet[$key])) {
                $toInsert[] = $doc;
            } else {
                $skipped++;
            }
        }

        if (empty($toInsert)) {
            return [
                'imported' => 0,
                'skipped' => $skipped,
                'errors' => $errors
            ];
        }

        // Inserção em massa com ordered=false para acelerar e evitar travar em erros
        try {
            Document::raw(function ($collection) use ($toInsert, &$imported) {
                $result = $collection->insertMany($toInsert, ['ordered' => false]);
                $imported += $result->getInsertedCount();
            });
        } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
            // Captura erros de escrita (ex: duplicatas em condição de corrida)
            $writeResult = $e->getWriteResult();
            $imported += $writeResult ? $writeResult->getInsertedCount() : 0;
            $skipped += $writeResult ? count($writeResult->getWriteErrors()) : 0;
            $errors[] = 'Erros de escrita em lote: ' . $e->getMessage();
            Log::warning('CSV Import Job: BulkWriteException', [
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            // Fallback: tentar inserir individualmente se o bulk falhar por outro motivo
            Log::error('CSV Import Job: Falha no insertMany, tentando individualmente.', [
                'error' => $e->getMessage()
            ]);
            foreach ($toInsert as $doc) {
                try {
                    Document::create($doc);
                    $imported++;
                } catch (\Exception $e2) {
                    $skipped++;
                    $errors[] = "Erro ao importar {$doc['filename']}: " . $e2->getMessage();
                }
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * Enviar notificação de conclusão do job
     */
    private function sendCompletionNotification($importedCount, $skippedCount, $duration)
    {
        try {
            Log::info("=== INICIANDO SALVAMENTO DE NOTIFICAÇÃO IMPORT ===", [
                'user_id' => $this->user->id,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'duration' => $duration
            ]);

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
            
            Log::info("=== NOTIFICAÇÃO DE IMPORT SALVA NO BANCO ===", [
                'user_id' => $this->user->id,
                'notification_id' => $notification ? $notification->id : null,
                'success' => (bool) $notification,
                'service' => 'EnhancedNotificationService'
            ]);

        } catch (\Exception $e) {
            Log::error("=== ERRO AO SALVAR NOTIFICAÇÃO DE IMPORT ===", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Enviar notificação de erro no job
     */
    private function sendErrorNotification($errorMessage, $duration)
    {
        try {
            Log::info("=== ENVIANDO NOTIFICAÇÃO DE ERRO IMPORT ===", [
                'user_id' => $this->user->id,
                'error' => $errorMessage,
                'duration' => $duration
            ]);

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
            
            Log::info("=== NOTIFICAÇÃO DE ERRO IMPORT SALVA ===", [
                'user_id' => $this->user->id,
                'notification_id' => $notification ? $notification->id : null,
                'success' => (bool) $notification
            ]);

        } catch (\Exception $e) {
            Log::error("=== ERRO AO SALVAR NOTIFICAÇÃO DE ERRO IMPORT ===", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function publishProgress(int $imported, int $skipped): void
    {
        try {
            $key = 'import:progress:' . $this->user->id;
            Redis::hmset($key, [
                'imported' => $imported,
                'skipped' => $skipped,
                'updated_at' => now()->toISOString(),
            ]);
            // Expirar em 1 hora
            Redis::expire($key, 3600);
        } catch (\Exception $e) {
            Log::debug('Falha ao publicar progresso no Redis', ['error' => $e->getMessage()]);
        }
    }
}
