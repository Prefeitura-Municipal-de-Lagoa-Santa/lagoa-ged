<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportDocumentRequest;
use App\Jobs\ImportDocumentsJob;
use App\Models\Document;
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Log;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\regex;
use SplFileObject;
use Storage;

class DocumentController extends Controller
{
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
        ]);

        // **CORREÇÃO para LogicException e Potencialmente para TypeError:**
        // Escapar caracteres especiais para regex e agrupar filtros principais em um único 'where' closure
        $query->where(function ($q) use ($filters) {

            // Filtro por Título (Busca parcial, case-insensitive)
            if (!empty($filters['title'])) {
                $pattern = new regex('.*' . preg_quote($filters['title'], '/') . '.*', 'i');
                $q->where('title', 'regex', $pattern);
            }

            // Filtro por Tags (assume string separada por vírgulas, busca se o documento contém ALGUMA das tags)
            if (!empty($filters['tags'])) {
                $tagsArray = array_map('trim', explode(',', $filters['tags']));
                $tagsArray = array_filter($tagsArray);

                if (!empty($tagsArray)) {
                    $q->where(function ($tagQuery) use ($tagsArray) {
                        foreach ($tagsArray as $tag) {
                            // Busca parcial (semelhante ao LIKE) e sem case sensitive
                            $regex = new regex($tag, 'i'); // 'i' ignora case
                            $tagQuery->orWhere('tags', 'regexp', $regex);
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
                $pattern = new regex('.*' . $escapedSearchText . '.*', 'i');

                $q->where(function ($orQuery) use ($pattern, $searchableMetadataFields) {
                    foreach ($searchableMetadataFields as $field) {
                        $orQuery->orWhere('metadata.' . $field, 'regex', $pattern);
                    }
                });
            }
        }); // Fim da closure principal que agrupa todos os filtros

        // 3. Paginação e Ordenação Final
        $documents = $query->orderBy('created_at', 'desc')->paginate(15)->appends($request->query());
        //dd($documents);
        return Inertia::render('documents/index', [
            'documents' => $documents,
            'filters' => $filters,
            'years' => $this->getAvailableYears(),
        ]);
    }

    // Método auxiliar para obter uma lista de anos (para o select do frontend)
    private function getAvailableYears(): array
    {
        $currentYear = Carbon::now()->year;
        $years = [];
        // Gera anos dos últimos 20 anos até o próximo ano (ou como desejar)
        for ($i = $currentYear + 1; $i >= $currentYear - 20; $i--) {
            $years[] = $i;
        }
        return $years;
    }

    public function view(Document $document)
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
        //dd($filePath);

        if (!Storage::disk('samba')->exists($filePath)) {
            abort(404, 'Arquivo não encontrado no compartilhamento.');
        }

        $fileName = $document->filename ?? basename($filePath);
        $mimeType = $document->mime_type ?? Storage::disk('samba')->mimeType($filePath);

        return Storage::disk('samba')->response($filePath, $fileName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
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
    
        return redirect()->back()->with('success', 'Importação em andamento. Você será notificado ao final.');
    }


    //public function processImport(ImportDocumentRequest $request)
    //{
        
    //    $user = Auth::user();
    //    if (!$user) {
    //        abort(401, 'Não autenticado.');
    //    }
//
    //    if (!$user->isAdmin()) {
    //        $userGroupObjectIds = $user->group_ids;
    //        $uploadersGroup = Group::where('name', 'UPLOADERS')->first();
//
    //        $canProcessImport = false;
    //        if ($uploadersGroup) {
    //            $canProcessImport = in_array((string) $uploadersGroup->_id, array_map(fn($id) => (string) $id, $userGroupObjectIds));
    //        }
//
    //        if (!$canProcessImport) {
    //            abort(403, 'Você não tem permissão para processar a importação de documentos.');
    //        }
    //    }
//
    //    $file = $request->file('csv_file');
    //    $filePath = $file->getPathname();
//
    //    $readGroupIds = collect(array_filter($request->input('read_group_ids', [])))
    //        ->map(fn($id) => new ObjectId($id))
    //        ->toArray();
//
    //    $writeGroupIds = collect(array_filter($request->input('write_group_ids', [])))
    //        ->map(fn($id) => new ObjectId($id))
    //        ->toArray();
//
    //    $importedCount = 0;
    //    $skippedCount = 0;
    //    $errors = [];
//
    //    try {
    //        $splFile = new SplFileObject($filePath, 'r');
    //        $splFile->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
//
    //        $header = [];
    //        $firstRow = true;
//
    //        foreach ($splFile as $row) {
    //            if ($firstRow) {
    //                $header = array_map('trim', $row);
    //                $firstRow = false;
    //                continue;
    //            }
//
    //            if (empty(array_filter($row))) {
    //                continue;
    //            }
//
    //            if (count($header) !== count($row)) {
    //                $errors[] = "Linha ignorada devido a número de colunas inconsistente: " . json_encode($row);
    //                Log::warning("CSV Import: Linha com colunas inconsistentes.", ['row' => $row]);
    //                $skippedCount++;
    //                continue;
    //            }
//
    //            $data = array_combine($header, array_map('trim', $row));
//
    //            $filename = $data['filename'] ?? null;
    //            $fileLocationPath = $data['file_location_path'] ?? null;
//
    //            if (!$filename || !$fileLocationPath) {
    //                $errors[] = "Linha ignorada por falta de 'filename' ou 'file_location_path': " . json_encode($data);
    //                Log::warning("CSV Import: Linha ignorada por falta de 'filename' ou 'file_location_path'.", ['data' => $data]);
    //                $skippedCount++;
    //                continue;
    //            }
//
    //            $existingDocument = Document::where('filename', $filename)
    //                ->where('file_location.path', $fileLocationPath)
    //                ->first();
//
    //            if ($existingDocument) {
    //                $errors[] = "Documento duplicado encontrado e ignorado: {$filename} em {$fileLocationPath}";
    //                Log::info("CSV Import: Documento duplicado ignorado.", ['filename' => $filename, 'path' => $fileLocationPath]);
    //                $skippedCount++;
    //                continue;
    //            }
//
    //            $documentData = [
    //                'title' => $data['title'] ?? null,
    //                'filename' => $filename,
    //                'file_extension' => $data['file_extension'] ?? null,
    //                'mime_type' => $data['mime_type'] ?? null,
    //                'upload_date' => isset($data['upload_date']) ? Carbon::parse($data['upload_date']) : Carbon::now(),
    //                'uploaded_by' => new ObjectId($user->id),
    //                'status' => $data['status'] ?? 'active',
    //            ];
//
    //            $documentData['metadata'] = [];
    //            $documentData['metadata']['document_type'] = $data['metadata_document_type'] ?? null;
    //            $documentData['metadata']['document_year'] = (int) ($data['metadata_document_year'] ?? 0);
//
    //            foreach ($data as $csvHeader => $value) {
    //                if (
    //                    str_starts_with($csvHeader, 'metadata_') &&
    //                    $csvHeader !== 'metadata_document_type' &&
    //                    $csvHeader !== 'metadata_document_year'
    //                ) {
    //                    $metadataFieldName = substr($csvHeader, strlen('metadata_'));
    //                    $documentData['metadata'][$metadataFieldName] = $value;
    //                }
    //            }
//
    //            if (isset($data['tags']) && !empty($data['tags'])) {
    //                $documentData['tags'] = array_map('trim', explode('|', $data['tags']));
    //            } else {
    //                $documentData['tags'] = [];
    //            }
//
    //            // ✅ Adiciona tag ADLP automaticamente se o tipo de documento for um dos desejados
    //            $documentType = strtoupper(trim($documentData['metadata']['document_type'] ?? ''));
    //            if (in_array($documentType, ['DECRETO', 'ATO', 'LEI', 'PORTARIA'])) {
    //                $documentData['tags'][] = 'ADLP';
    //            }
//
    //            // ✅ Evita tags duplicadas
    //            $documentData['tags'] = array_map(fn($tag) => strtoupper(trim($tag)), $documentData['tags']);
    //            $documentData['tags'] = array_unique($documentData['tags']);
//
    //            $documentData['permissions'] = [
    //                'read_group_ids' => $readGroupIds,
    //                'write_group_ids' => $writeGroupIds,
    //            ];
//
    //            $documentData['file_location'] = [
    //                'path' => $fileLocationPath,
    //                'storage_type' => $data['file_location_storage_type'] ?? 'file_server',
    //                'bucket_name' => $data['file_location_bucket_name'] ?? null,
    //            ];
//
    //            Document::create($documentData);
    //            $importedCount++;
    //            Log::info("CSV Import: Documento importado: {$filename}");
    //        }
//
    //        unlink($filePath);
//
    //        $message = "Importação concluída. Total importados: {$importedCount}, Total ignorados: {$skippedCount}.";
    //        if (!empty($errors)) {
    //            $message .= " Verifique os detalhes dos erros abaixo.";
    //            return redirect()->back()->with('success', $message)->with('importErrors', $errors);
    //        }
//
    //        return redirect()->back()->with('success', $message);
//
    //    } catch (\Exception $e) {
    //        Log::error("CSV Import Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    //        $errors[] = "Ocorreu um erro inesperado: " . $e->getMessage();
    //        return redirect()->back()->with('error', "Ocorreu um erro grave durante a importação. Verifique os logs do servidor.")->with('importErrors', $errors);
    //    }
   // }

}