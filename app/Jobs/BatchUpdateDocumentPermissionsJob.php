<?php

namespace App\Jobs;

use App\Models\Document;
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
        // Converter os IDs para ObjectIds usando a mesma abordagem do ImportDocumentsJob
        //$readGroupObjectIds = collect(array_filter($this->readGroupIds))
        //    ->map(fn($id) => new ObjectId($id))
        //    ->toArray();

        //$writeGroupObjectIds = collect(array_filter($this->writeGroupIds))
        //    ->map(fn($id) => new ObjectId($id))
        //    ->toArray();

        foreach ($this->documentIds as $docId) {
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
                $permissions['read_group_ids'] = $readGroupIds;
                $permissions['write_group_ids'] = $writeGroupIds;
                
                // Atualizar o documento
                $document->permissions = $permissions;
                $result = $document->save();

                Log::info("Permissões do documento {$docId} atualizadas pelo usuário {$this->userId}", [
                    'read_groups_count' => count($readGroupIds),
                    'write_groups_count' => count($writeGroupIds),
                    'save_result' => $result,
                    'permissions_after' => $document->fresh()->permissions
                ]);
            }
        }
    }
}
