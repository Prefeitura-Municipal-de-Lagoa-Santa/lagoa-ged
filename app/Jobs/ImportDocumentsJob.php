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

class ImportDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $tempPath;
    public $readGroupIds;
    public $writeGroupIds;

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

        try {
            $splFile = new \SplFileObject($filePath, 'r');
            $splFile->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);

            $header = [];
            $firstRow = true;

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

                $existingDocument = Document::where('filename', $filename)
                    ->where('file_location.path', $fileLocationPath)
                    ->first();

                if ($existingDocument) {
                    $errors[] = "Documento duplicado ignorado: {$filename} em {$fileLocationPath}";
                    Log::info("CSV Import Job: Documento duplicado.", ['filename' => $filename]);
                    $skippedCount++;
                    continue;
                }

                $documentData = [
                    'title' => $data['title'] ?? null,
                    'filename' => $filename,
                    'file_extension' => $data['file_extension'] ?? null,
                    'mime_type' => $data['mime_type'] ?? null,
                    'upload_date' => isset($data['upload_date']) ? \Carbon\Carbon::parse($data['upload_date']) : \Carbon\Carbon::now(),
                    'uploaded_by' => new \MongoDB\BSON\ObjectId($user->id),
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
                    'path' => $fileLocationPath,
                    'storage_type' => $data['file_location_storage_type'] ?? 'file_server',
                    'bucket_name' => $data['file_location_bucket_name'] ?? null,
                ];

                Document::create($documentData);
                $importedCount++;
                Log::info("CSV Import Job: Documento importado - {$filename}");
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
}
