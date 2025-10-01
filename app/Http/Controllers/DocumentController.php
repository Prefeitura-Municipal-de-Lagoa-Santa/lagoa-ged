<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchUpdateDocumentPermissionsRequest;
use App\Http\Requests\ImportDocumentRequest;
use App\Jobs\BatchUpdateDocumentPermissionsJob;
use App\Jobs\ImportDocumentsJob;
use App\Models\Document;
use App\Models\Group;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Log;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\regex;
use SplFileObject;
use Illuminate\Support\Facades\Redis;

class DocumentController extends Controller
{
    // Removido middleware de conversão de flash para evitar duplicação
    // O sistema de notificações agora funciona diretamente via EnhancedNotificationService
    /**
     * Exibe o formulário de edição de um documento
     */
    public function edit(Document $document)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Não autenticado.');
        }

        $canEdit = false;
        if ($user->isAdmin()) {
            $canEdit = true;
        } else {
            $userGroupObjectIds = $user->group_ids;
            $documentWriteGroupIds = $document->permissions['write_group_ids'] ?? [];
            $userGroupStrings = array_map(fn($id) => (string) $id, $userGroupObjectIds);
            $documentWriteGroupStrings = array_map(fn($id) => (string) $id, $documentWriteGroupIds);
            $canEdit = !empty(array_intersect($userGroupStrings, $documentWriteGroupStrings));
        }
        if (!$canEdit) {
            abort(403, 'Você não tem permissão para editar este documento.');
        }

        $groups = Group::all(['_id', 'name']);
        return Inertia::render('documents/edit', [
            'document' => $document,
            'groups' => $groups,
        ]);
    }

    /**
     * Atualiza os dados do documento
     */
    public function update(\App\Http\Requests\UpdateDocumentRequest $request, Document $document)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Não autenticado.');
        }

        $canEdit = false;
        if ($user->isAdmin()) {
            $canEdit = true;
        } else {
            $userGroupObjectIds = $user->group_ids;
            $documentWriteGroupIds = $document->permissions['write_group_ids'] ?? [];
            $userGroupStrings = array_map(fn($id) => (string) $id, $userGroupObjectIds);
            $documentWriteGroupStrings = array_map(fn($id) => (string) $id, $documentWriteGroupIds);
            $canEdit = !empty(array_intersect($userGroupStrings, $documentWriteGroupStrings));
        }
        if (!$canEdit) {
            abort(403, 'Você não tem permissão para editar este documento.');
        }

        $validated = $request->validated();

        $document->title = $validated['title'];
        if (isset($validated['metadata'])) {
            $document->metadata = $validated['metadata'];
        }
        if (isset($validated['tags'])) {
            $document->tags = $validated['tags'];
        }
        if (isset($validated['permissions'])) {
            $document->permissions = array_merge($document->permissions ?? [], $validated['permissions']);
        }

        $document->save();

        // Usar EnhancedNotificationService diretamente
        $notificationService = app(\App\Services\EnhancedNotificationService::class);
        $notificationService->success(
            $user->id,
            'Documento Atualizado',
            'Documento atualizado com sucesso!',
            ['document_id' => $document->_id, 'document_title' => $document->title]
        );

        return redirect()->route('documents.show', $document->_id);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return Inertia::render('documents/index', [
                'documents' => (object) ['data' => []],
                'filters' => $request->only(['title', 'tags', 'document_year', 'other_metadata']),
                'years' => $this->getAvailableYears(),
            ]);
        }

        $query = Document::query();

        // 1. --- Lógica de Permissão (Admin vs. Usuário Comum) ---
        if (!$user->isAdmin()) {
            $userGroupObjectIds = $user->group_ids;
            if (empty($userGroupObjectIds)) {
                return Inertia::render('documents/index', [
                    'documents' => (object) ['data' => []],
                    'filters' => $request->only(['title', 'tags', 'document_year', 'other_metadata']),
                    'years' => $this->getAvailableYears(),
                    'user' => $user,
                ]);
            }
            $query->whereIn('permissions.read_group_ids', $userGroupObjectIds);
        }


        // 2. --- Aplicação de Filtros Dinâmicos ---
        $filters = $request->only([
            'title',
            'tags',
            'document_year',
            'other_metadata',
            'per_page',
        ]);

        // **CORREÇÃO para LogicException e Potencialmente para TypeError:**
        // Escapar caracteres especiais para regex e agrupar filtros principais em um único 'where' closure
        $query->where(function ($q) use ($filters) {

            // Filtro por Título (Busca parcial, case-insensitive)
            if (!empty($filters['title'])) {
                $q->where('title', 'regex', '/' . preg_quote($filters['title'], '/') . '/i');
            }

            // Filtro por Tags (assume string separada por vírgulas, busca se o documento contém ALGUMA das tags)
            if (!empty($filters['tags'])) {
                $tagsArray = array_map('trim', explode(',', $filters['tags']));
                $tagsArray = array_filter($tagsArray);

                if (!empty($tagsArray)) {
                    $q->where(function ($tagQuery) use ($tagsArray) {
                        foreach ($tagsArray as $tag) {
                            // Busca parcial (semelhante ao LIKE) e sem case sensitive
                            $tagQuery->orWhere('tags', 'regex', '/' . preg_quote($tag, '/') . '/i');
                        }
                    });
                }
            }


            // Filtro por Ano do Documento (metadata.document_year)
            if (!empty($filters['document_year'])) {
                $year = (int) $filters['document_year'];
                if ($year > 0) {
                    $q->where('metadata.document_year', $year);
                }
            }

            $searchableMetadataFields = [
                'document_number',
                'document_type',
            ];
            // Filtro por Outros Metadados Dinâmicos (um único campo de texto livre)
            if (!empty($filters['other_metadata']) && is_string($filters['other_metadata'])) {
                $escapedSearchText = preg_quote($filters['other_metadata'], '/');
                $pattern = '/' . $escapedSearchText . '/i';

                $q->where(function ($orQuery) use ($pattern, $searchableMetadataFields) {
                    foreach ($searchableMetadataFields as $field) {
                        $orQuery->orWhere('metadata.' . $field, 'regex', $pattern);
                    }
                });
            }
        }); // Fim da closure principal que agrupa todos os filtros

        // 3. Paginação e Ordenação Final
        $perPage = $request->input('per_page', 25);
        
        // Force HTTPS before pagination
        \Illuminate\Support\Facades\URL::forceScheme('https');
        
        $documents = $query->orderBy('created_at', 'desc')->paginate($perPage)->appends($request->query());
        
        // Force HTTPS in pagination URLs after creation
        $documents->withPath(str_replace('http://', 'https://', $request->url()));
        
        //dd($documents);
        
        return Inertia::render('documents/index', [
            'documents' => $documents,
            'filters' => $filters,
            'years' => $this->getAvailableYears(),
            'user' => $user,
        ]);
    }

    // Método auxiliar para obter uma lista de anos (para o select do frontend)
    private function getAvailableYears(): array
    {
        $result = Document::distinct('metadata.document_year')->get()->toArray();
       //dd($result);
        $years = array_column($result, '0');
        //dd($years);
        return $years;
    }

    public function view(Document $document, Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Não autenticado.');
        }

        // --- Lógica de permissão do Admin ---
        if (!$user->isAdmin()) {
            // Se NÃO for admin, verifica a permissão de leitura
            $userGroupObjectIds = $user->group_ids;
            $documentReadGroupIds = $document->permissions['read_group_ids'] ?? [];

            $userGroupStrings = array_map(fn($id) => (string) $id, $userGroupObjectIds);
            $documentReadGroupStrings = array_map(fn($id) => (string) $id, $documentReadGroupIds);

            $canRead = !empty(array_intersect($userGroupStrings, $documentReadGroupStrings));

            if (!$canRead) {
                abort(403, 'Você não tem permissão para visualizar este arquivo.');
            }
        }
        // --- Fim da lógica de permissão do Admin ---

        $filePath = $document->file_location['path'];
        
        // Converter caminhos do Windows para Linux
        $filePath = str_replace('\\', '/', $filePath);
        // Converter extensões maiúsculas para minúsculas (caso necessário)
        $filePath = preg_replace('/\.PDF$/', '.pdf', $filePath);
        
        $fileName = $document->filename ?? basename($filePath);
        $mimeType = $document->mime_type ?? 'application/octet-stream';
        
        // Verificar se é um download forçado
        $disposition = $request->has('download') ? 'attachment' : 'inline';

        // Acesso via caminho montado em disco (unificado para local e produção)
        $basePath = config('filesystems.disks.samba.root', '/var/www/html/storage/documentos/');

        // Garantir que o basePath termina com /
        if (!str_ends_with($basePath, '/')) {
            $basePath .= '/';
        }

        $fullPath = $basePath . ltrim($filePath, '/');

        if (!file_exists($fullPath)) {
            // Log para debug
            \Log::error('Arquivo não encontrado', [
                'fullPath' => $fullPath,
                'basePath' => $basePath,
                'filePath' => $filePath,
                'document_id' => $document->id,
                'config_samba_root' => config('filesystems.disks.samba.root')
            ]);
            abort(404, 'Arquivo não encontrado no compartilhamento.');
        }

        // Obter mime type do arquivo se não existir no documento
        if ($document->mime_type === null) {
            $mimeType = mime_content_type($fullPath) ?? 'application/octet-stream';
        }

        return response()->stream(function () use ($fullPath) {
            readfile($fullPath);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition . '; filename="' . $fileName . '"',
            'Content-Length' => filesize($fullPath),
        ]);
    }

    public function show(Document $document)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Não autenticado.');
        }

        $canEdit = false; // Inicializa a flag de edição

        // --- Lógica de permissão do Admin ---
        if ($user->isAdmin()) {
            // Admin pode ver e editar tudo
            $canRead = true;
            $canEdit = true;
        } else {
            // Se NÃO for admin, verifica permissão de leitura
            $userGroupObjectIds = $user->group_ids;
            $documentReadGroupIds = $document->permissions['read_group_ids'] ?? [];

            $userGroupStrings = array_map(fn($id) => (string) $id, $userGroupObjectIds);
            $documentReadGroupStrings = array_map(fn($id) => (string) $id, $documentReadGroupIds);

            $canRead = !empty(array_intersect($userGroupStrings, $documentReadGroupStrings));

            if ($canRead) { // Se pode ler, verifica se pode editar
                $documentWriteGroupIds = $document->permissions['write_group_ids'] ?? [];
                $documentWriteGroupStrings = array_map(fn($id) => (string) $id, $documentWriteGroupIds);
                $canEdit = !empty(array_intersect($userGroupStrings, $documentWriteGroupStrings));
            }
        }
        // --- Fim da lógica de permissão do Admin ---

        if (!$canRead) {
            abort(403, 'Você não tem permissão para acessar os detalhes deste documento.');
        }
        //dd($document->file_location['path']);
        return Inertia::render('documents/show', [
            'document' => $document,
            'canEdit' => $canEdit,
        ]);
    }

    public function import()
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Não autenticado.');
        }

        // --- Lógica de permissão do Admin ---
        if (!$user->isAdmin()) {
            // Se NÃO for admin, verifica se pertence ao grupo "UPLOADERS"
            $userGroupObjectIds = $user->group_ids;
            $uploadersGroup = Group::where('name', 'UPLOADERS')->first();

            $canAccessImport = false;
            if ($uploadersGroup) {
                $canAccessImport = in_array((string) $uploadersGroup->_id, array_map(fn($id) => (string) $id, $userGroupObjectIds));
            }

            if (!$canAccessImport) {
                abort(403, 'Você não tem permissão para importar documentos.');
            }
        }
        // --- Fim da lógica de permissão do Admin ---

        $groups = Group::whereNot('name', 'ADMINISTRADORES')->get(['_id', 'name']);

        return Inertia::render('documents/import', [
            'groups' => $groups,
        ]);
    }

    public function processImport(ImportDocumentRequest $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Não autenticado.');
        }

        if (!$user->isAdmin()) {
            $userGroupObjectIds = $user->group_ids;
            $uploadersGroup = Group::where('name', 'UPLOADERS')->first();

            $canProcessImport = false;
            if ($uploadersGroup) {
                $canProcessImport = in_array((string) $uploadersGroup->_id, array_map(fn($id) => (string) $id, $userGroupObjectIds));
            }

            if (!$canProcessImport) {
                abort(403, 'Você não tem permissão para processar a importação de documentos.');
            }
        }

        $file = $request->file('csv_file');
        $tempPath = $file->store('imports/tmp');

        ImportDocumentsJob::dispatch(
            $user,
            $tempPath,
            $request->input('read_group_ids', []),
            $request->input('write_group_ids', [])
        );

        // Usar EnhancedNotificationService diretamente
        $notificationService = app(\App\Services\EnhancedNotificationService::class);
        $notificationService->info(
            $user->id,
            'Importação Iniciada',
            'Importação em andamento. Você será notificado ao final.',
            ['job_type' => 'import_documents']
        );

        return redirect()->back();
    }

    public function batchPermissions(Request $request)
    {
        $query = Document::query();

        // Filtros semelhantes à index usando MongoDB regex
        $filters = $request->only(['title', 'tags', 'document_year', 'other_metadata', 'per_page']);

        $query->where(function ($q) use ($filters) {
            // Filtro por Título
            if (!empty($filters['title'])) {
                $q->where('title', 'regex', '/' . preg_quote($filters['title'], '/') . '/i');
            }

            // Filtro por Tags
            if (!empty($filters['tags'])) {
                $tagsArray = array_map('trim', explode(',', $filters['tags']));
                $tagsArray = array_filter($tagsArray);

                if (!empty($tagsArray)) {
                    $q->where(function ($tagQuery) use ($tagsArray) {
                        foreach ($tagsArray as $tag) {
                            $tagQuery->orWhere('tags', 'regex', '/' . preg_quote($tag, '/') . '/i');
                        }
                    });
                }
            }

            // Filtro por Ano do Documento
            if (!empty($filters['document_year'])) {
                $year = (int) $filters['document_year'];
                if ($year > 0) {
                    $q->where('metadata.document_year', $year);
                }
            }

            // Filtro por Outros Metadados
            if (!empty($filters['other_metadata']) && is_string($filters['other_metadata'])) {
                $escapedSearchText = preg_quote($filters['other_metadata'], '/');
                $pattern = '/' . $escapedSearchText . '/i';

                $searchableMetadataFields = ['document_number', 'document_type'];
                $q->where(function ($orQuery) use ($pattern, $searchableMetadataFields) {
                    foreach ($searchableMetadataFields as $field) {
                        $orQuery->orWhere('metadata.' . $field, 'regex', $pattern);
                    }
                });
            }
        });

        $perPage = $request->input('per_page', 25);
        $documents = $query->select(['id', 'title', 'metadata'])->paginate($perPage)->withQueryString();
        $groups = Group::select(['id', 'name'])->get();

        // Para o filtro de anos
        $years = $this->getAvailableYears();

        return Inertia::render('documents/batch-permissions', [
            'documents' => $documents,
            'groups' => $groups,
            'filters' => $filters,
            'years' => $years,
        ]);
    }

    public function batchPermissionsUpdate(BatchUpdateDocumentPermissionsRequest $request)
    {
        Log::info('batchPermissionsUpdate chamado', [
            'request_data' => $request->all(),
            'is_preview' => $request->input('preview', false)
        ]);

        $documentIds = $request->input('document_ids', []);
        $readGroupIds = $request->input('read_group_ids', []);
        $writeGroupIds = $request->input('write_group_ids', []);

        // Converter os IDs para ObjectIds usando a mesma abordagem do ImportDocumentsJob
        $readGroupObjectIds = collect(array_filter($readGroupIds))
            ->map(fn($id) => new ObjectId($id))
            ->toArray();

        $writeGroupObjectIds = collect(array_filter($writeGroupIds))
            ->map(fn($id) => new ObjectId($id))
            ->toArray();

        // Se for apenas preview, retorna as mudanças
        if ($request->input('preview', false)) {
            Log::info('Executando preview das mudanças');
            return $this->previewBatchChanges($documentIds, $readGroupIds, $writeGroupIds);
        }

        Log::info('Executando atualização em lote', [
            'document_count' => count($documentIds),
            'read_groups_count' => count($readGroupObjectIds),
            'write_groups_count' => count($writeGroupObjectIds)
        ]);
        //dd($readGroupObjectIds, $writeGroupObjectIds);
        BatchUpdateDocumentPermissionsJob::dispatch($documentIds, $readGroupObjectIds, $writeGroupObjectIds, (string) $request->user()->id);

        // Usar EnhancedNotificationService diretamente
        $notificationService = app(\App\Services\EnhancedNotificationService::class);
        $notificationService->info(
            $request->user()->id,
            'Atualização em Lote Iniciada',
            'Atualização em lote iniciada!',
            ['job_type' => 'batch_permissions', 'document_count' => count($documentIds)]
        );

        return redirect()->route('documents.index');
    }

    private function previewBatchChanges($documentIds, $readGroupIds, $writeGroupIds)
    {
        $documents = Document::whereIn('_id', $documentIds)->get(['_id', 'title', 'permissions', 'metadata']);
        $allGroupIds = array_merge($readGroupIds, $writeGroupIds);
        $groups = Group::whereIn('_id', $allGroupIds)->get(['_id', 'name']);

        // Criar mapa de grupos para facilitar lookup
        $groupMap = $groups->keyBy('_id')->map(fn($group) => $group->name);

        $changes = [];
        $summary = [
            'total_documents' => count($documents),
            'documents_with_changes' => 0,
            'documents_unchanged' => 0,
            'total_permissions_added' => 0,
            'total_permissions_removed' => 0,
        ];

        foreach ($documents as $document) {
            $currentReadGroups = $document->permissions['read_group_ids'] ?? [];
            $currentWriteGroups = $document->permissions['write_group_ids'] ?? [];

            // Converter ObjectIds atuais para strings para comparação
            $currentReadGroupStrings = array_map(fn($id) => (string) $id, $currentReadGroups);
            $currentWriteGroupStrings = array_map(fn($id) => (string) $id, $currentWriteGroups);

            // Calcular diferenças
            $readGroupsToAdd = array_diff($readGroupIds, $currentReadGroupStrings);
            $readGroupsToRemove = array_diff($currentReadGroupStrings, $readGroupIds);
            $writeGroupsToAdd = array_diff($writeGroupIds, $currentWriteGroupStrings);
            $writeGroupsToRemove = array_diff($currentWriteGroupStrings, $writeGroupIds);

            // Obter nomes dos grupos para exibição
            $readGroupNamesToAdd = array_filter(array_map(fn($id) => $groupMap->get($id), $readGroupsToAdd));
            $readGroupNamesToRemove = array_filter(array_map(fn($id) => $groupMap->get($id), $readGroupsToRemove));
            $writeGroupNamesToAdd = array_filter(array_map(fn($id) => $groupMap->get($id), $writeGroupsToAdd));
            $writeGroupNamesToRemove = array_filter(array_map(fn($id) => $groupMap->get($id), $writeGroupsToRemove));

            $hasChanges = !empty($readGroupsToAdd) || !empty($readGroupsToRemove) ||
                !empty($writeGroupsToAdd) || !empty($writeGroupsToRemove);

            if ($hasChanges) {
                $summary['documents_with_changes']++;
                $summary['total_permissions_added'] += count($readGroupsToAdd) + count($writeGroupsToAdd);
                $summary['total_permissions_removed'] += count($readGroupsToRemove) + count($writeGroupsToRemove);

                $changes[] = [
                    'document_id' => (string) $document->_id,
                    'document_title' => $document->title,
                    'document_type' => $document->metadata['document_type'] ?? 'N/A',
                    'current_permissions' => [
                        'read_groups' => array_filter(array_map(fn($id) => $groupMap->get($id), $currentReadGroupStrings)),
                        'write_groups' => array_filter(array_map(fn($id) => $groupMap->get($id), $currentWriteGroupStrings)),
                    ],
                    'new_permissions' => [
                        'read_groups' => array_filter(array_map(fn($id) => $groupMap->get($id), $readGroupIds)),
                        'write_groups' => array_filter(array_map(fn($id) => $groupMap->get($id), $writeGroupIds)),
                    ],
                    'changes' => [
                        'read_groups_to_add' => $readGroupNamesToAdd,
                        'read_groups_to_remove' => $readGroupNamesToRemove,
                        'write_groups_to_add' => $writeGroupNamesToAdd,
                        'write_groups_to_remove' => $writeGroupNamesToRemove,
                    ],
                    'change_count' => count($readGroupsToAdd) + count($readGroupsToRemove) +
                        count($writeGroupsToAdd) + count($writeGroupsToRemove),
                ];
            } else {
                $summary['documents_unchanged']++;
            }
        }

        // Ordenar documentos por quantidade de mudanças (mais mudanças primeiro)
        usort($changes, fn($a, $b) => $b['change_count'] <=> $a['change_count']);

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'changes' => $changes,
            'groups' => $groups,
            'preview_timestamp' => now()->toISOString(),
            'warning_messages' => $this->generateWarningMessages($changes, $summary),
        ]);
    }

    private function generateWarningMessages($changes, $summary)
    {
        $warnings = [];

        // Avisos sobre remoção de permissões
        if ($summary['total_permissions_removed'] > 0) {
            $warnings[] = "ATENÇÃO: {$summary['total_permissions_removed']} permissões serão REMOVIDAS.";
        }

        // Avisos sobre documentos sem permissões
        $documentsWithoutRead = array_filter(
            $changes,
            fn($change) =>
            empty($change['new_permissions']['read_groups'])
        );

        if (!empty($documentsWithoutRead)) {
            $warnings[] = "CUIDADO: " . count($documentsWithoutRead) . " documento(s) ficarão SEM permissões de leitura.";
        }

        $documentsWithoutWrite = array_filter(
            $changes,
            fn($change) =>
            empty($change['new_permissions']['write_groups'])
        );

        if (!empty($documentsWithoutWrite)) {
            $warnings[] = "CUIDADO: " . count($documentsWithoutWrite) . " documento(s) ficarão SEM permissões de escrita.";
        }

        return $warnings;
    }

    public function getNotifications(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            Log::warning('getNotifications: Usuário não autenticado');
            return response()->json(['success' => false, 'notifications' => []]);
        }

        // Usar o novo serviço de notificações
        $notificationService = app(\App\Services\EnhancedNotificationService::class);

        // Buscar últimas 5 notificações não lidas para o popup
        $recentNotifications = $notificationService->getForUser($user->id, true, null, null, 5);
        $unreadCount = $notificationService->getUnreadCount($user->id);

        // Converter para formato compatível com frontend
        $formattedNotifications = $recentNotifications->map(function ($notification) {
            return [
                'id' => (string) $notification->_id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'category' => $notification->category,
                'data' => $notification->data,
                'created_at' => $notification->created_at->toISOString(),
                'time_ago' => $notification->created_at->diffForHumans(),
                'is_read' => $notification->isRead()
            ];
        });

        return response()->json([
            'success' => true,
            'notifications' => $formattedNotifications,
            'unread_count' => $unreadCount,
            // Para compatibilidade com código antigo - usar apenas se não há notificações do novo sistema
            'notification' => $formattedNotifications->first()['message'] ?? null
        ]);
    }




}