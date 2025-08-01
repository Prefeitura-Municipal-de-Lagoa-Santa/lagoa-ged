<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;

class BatchUpdateDocumentPermissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $documentIds;
    protected $readGroupIds;
    protected $writeGroupIds;
    protected $userId;

    public function __construct(array $documentIds, array $readGroupIds, array $writeGroupIds, $userId)
    {
        $this->documentIds = $documentIds;
        $this->readGroupIds = $readGroupIds;
        $this->writeGroupIds = $writeGroupIds;
        $this->userId = $userId;
    }

    public function handle()
    {
        Log::info("=== JOB INICIADO ===", [
            'user_id' => $this->userId,
            'document_count' => count($this->documentIds)
        ]);

        // Os IDs já vêm como ObjectIds do Controller
        $readGroupObjectIds = $this->readGroupIds;
        $writeGroupObjectIds = $this->writeGroupIds;

        $successCount = 0;
        $errorCount = 0;
        $startTime = now();

        Log::info("Iniciando atualização em lote de permissões", [
            'user_id' => $this->userId,
            'total_documents' => count($this->documentIds),
            'read_groups_count' => count($readGroupObjectIds),
            'write_groups_count' => count($writeGroupObjectIds),
            'start_time' => $startTime
        ]);

        foreach ($this->documentIds as $docId) {
            try {
                $document = Document::find($docId);
                if ($document) {
                    Log::info("Processando documento {$docId} - permissões antes:", [
                        'permissions_before' => $document->permissions,
                        'read_groups_input' => $this->readGroupIds,
                        'write_groups_input' => $this->writeGroupIds
                    ]);

                    // Inicializar permissions se não existir
                    $permissions = $document->permissions ?? [];
                    
                    // Usar a mesma abordagem do ImportDocumentsJob
                    $permissions['read_group_ids'] = $readGroupObjectIds;
                    $permissions['write_group_ids'] = $writeGroupObjectIds;
                    
                    // Atualizar o documento
                    $document->permissions = $permissions;
                    $result = $document->save();

                    if ($result) {
                        $successCount++;
                        Log::info("Permissões do documento {$docId} atualizadas com sucesso pelo usuário {$this->userId}", [
                            'read_groups_count' => count($readGroupObjectIds),
                            'write_groups_count' => count($writeGroupObjectIds),
                            'permissions_after' => $document->fresh()->permissions
                        ]);
                    } else {
                        $errorCount++;
                        Log::error("Falha ao salvar documento {$docId}");
                    }
                } else {
                    $errorCount++;
                    Log::error("Documento {$docId} não encontrado");
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Erro ao processar documento {$docId}: " . $e->getMessage(), [
                    'exception' => $e->getTraceAsString()
                ]);
            }
        }

        $endTime = now();
        $duration = abs($endTime->diffInSeconds($startTime));
        
        // Garantir que a duração seja pelo menos 1 segundo se houve processamento
        if ($duration == 0 && ($successCount > 0 || $errorCount > 0)) {
            $duration = 1;
        }

        // Log de finalização
        Log::info("Atualização em lote de permissões finalizada", [
            'user_id' => $this->userId,
            'total_documents' => count($this->documentIds),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'duration_seconds' => $duration,
            'end_time' => $endTime
        ]);

        // Salvar resultado na sessão do usuário para notificação
        $this->storeJobResult($successCount, $errorCount, $duration);
    }

    private function storeJobResult($successCount, $errorCount, $duration)
    {
        try {
            Log::info("=== INICIANDO SALVAMENTO DE NOTIFICAÇÃO ===", [
                'user_id' => $this->userId,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'duration' => $duration
            ]);

            // Usar o novo serviço de notificações
            $notificationService = new \App\Services\EnhancedNotificationService();
            $notification = $notificationService->jobCompleted(
                $this->userId,
                'Atualização de Permissões',
                $successCount,
                $errorCount,
                $duration,
                [
                    'document_ids' => $this->documentIds,
                    'total_documents' => count($this->documentIds)
                ]
            );
            
            Log::info("=== NOTIFICAÇÃO SALVA NO BANCO ===", [
                'user_id' => $this->userId,
                'notification_id' => $notification ? $notification->id : null,
                'success' => (bool) $notification,
                'service' => 'EnhancedNotificationService'
            ]);

        } catch (\Exception $e) {
            Log::error("=== ERRO AO SALVAR NOTIFICAÇÃO ===", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
