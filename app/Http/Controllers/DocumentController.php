<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportDocumentRequest;
use App\Models\Document;
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Log;
use MongoDB\BSON\ObjectId;
use SplFileObject;
use Storage;

class DocumentController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return Inertia::render('documents/index', [
                'documents' => (object)['data' => []],
                'filters' => [],
            ]);
        }

        // --- Lógica de permissão do Admin ---
        if ($user->isAdmin()) {
            // Se for admin, mostra TODOS os documentos
            $documents = Document::query()
                ->orderBy('created_at', 'desc')
                ->paginate(15);
                //dd($documents);
            
        } else {
            // Se não for admin, aplica o filtro por grupos de leitura
            $userGroupObjectIds = $user->group_ids;
            //dd($userGroupObjectIds);
            if (empty($userGroupObjectIds)) {
                return Inertia::render('documents/index', [
                    'documents' => (object)['data' => []],
                    'filters' => [],
                ]);
            }
            //$userGroupObjectIds = ['686febf89895f15f3c083e94'];
            $documents = Document::whereIn('permissions.read_group_ids', $userGroupObjectIds)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
                //dd($documents);
        }
        // --- Fim da lógica de permissão do Admin ---

        return Inertia::render('documents/index', [
            'documents' => $documents,
            'filters' => [],
        ]);
    }

    public function view(Document $document)
    {
        $user = Auth::user();
        if (!$user) { abort(401, 'Não autenticado.'); }

        // --- Lógica de permissão do Admin ---
        if (!$user->isAdmin()) {
            // Se NÃO for admin, verifica a permissão de leitura
            $userGroupObjectIds = $user->group_ids;
            $documentReadGroupIds = $document->permissions['read_group_ids'] ?? [];

            $userGroupStrings = array_map(fn($id) => (string)$id, $userGroupObjectIds);
            $documentReadGroupStrings = array_map(fn($id) => (string)$id, $documentReadGroupIds);

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

        return Storage::disk('samba')->response($filePath, $fileName,[
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function show(Document $document)
    {
        $user = Auth::user();
        if (!$user) { abort(401, 'Não autenticado.'); }

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

            $userGroupStrings = array_map(fn($id) => (string)$id, $userGroupObjectIds);
            $documentReadGroupStrings = array_map(fn($id) => (string)$id, $documentReadGroupIds);

            $canRead = !empty(array_intersect($userGroupStrings, $documentReadGroupStrings));

            if ($canRead) { // Se pode ler, verifica se pode editar
                $documentWriteGroupIds = $document->permissions['write_group_ids'] ?? [];
                $documentWriteGroupStrings = array_map(fn($id) => (string)$id, $documentWriteGroupIds);
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
        if (!$user) { abort(401, 'Não autenticado.'); }

        // --- Lógica de permissão do Admin ---
        if (!$user->isAdmin()) {
            // Se NÃO for admin, verifica se pertence ao grupo "UPLOADERS"
            $userGroupObjectIds = $user->group_ids;
            $uploadersGroup = Group::where('name', 'UPLOADERS')->first();

            $canAccessImport = false;
            if ($uploadersGroup) {
                $canAccessImport = in_array((string)$uploadersGroup->_id, array_map(fn($id) => (string)$id, $userGroupObjectIds));
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
        if (!$user) { abort(401, 'Não autenticado.'); }

        // --- Lógica de permissão do Admin ---
        if (!$user->isAdmin()) {
            // Se NÃO for admin, verifica se pertence ao grupo "UPLOADERS"
            $userGroupObjectIds = $user->group_ids;
            $uploadersGroup = Group::where('name', 'UPLOADERS')->first();

            $canProcessImport = false;
            if ($uploadersGroup) {
                $canProcessImport = in_array((string)$uploadersGroup->_id, array_map(fn($id) => (string)$id, $userGroupObjectIds));
            }

            if (!$canProcessImport) {
                abort(403, 'Você não tem permissão para processar a importação de documentos.');
            }
        }
        // --- Fim da lógica de permissão do Admin ---

        // Restante do seu código de importação...
        $file = $request->file('csv_file');
        $filePath = $file->getPathname();

        $readGroupIdsInput = array_filter($request->input('read_group_ids', []));
        $writeGroupIdsInput = array_filter($request->input('write_group_ids', []));

        $readGroupIds = collect($readGroupIdsInput)
                                ->map(fn ($id) => new ObjectId($id))
                                ->toArray();
        $writeGroupIds = collect($writeGroupIdsInput)
                                ->map(fn ($id) => new ObjectId($id))
                                ->toArray();

        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        try {
            $splFile = new SplFileObject($filePath, 'r');
            $splFile->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);

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
                    $errors[] = "Linha ignorada devido a número de colunas inconsistente: " . json_encode($row);
                    Log::warning("CSV Import: Linha com colunas inconsistentes.", ['row' => $row]);
                    $skippedCount++;
                    continue;
                }

                $data = array_combine($header, array_map('trim', $row));

                $filename = $data['filename'] ?? null;
                $fileLocationPath = $data['file_location_path'] ?? null;

                if (!$filename || !$fileLocationPath) {
                    $errors[] = "Linha ignorada por falta de 'filename' ou 'file_location_path': " . json_encode($data);
                    Log::warning("CSV Import: Linha ignorada por falta de 'filename' ou 'file_location_path'.", ['data' => $data]);
                    $skippedCount++;
                    continue;
                }

                $existingDocument = Document::where('filename', $filename)
                    ->where('file_location.path', $fileLocationPath)
                    ->first();

                if ($existingDocument) {
                    $errors[] = "Documento duplicado encontrado e ignorado: {$filename} em {$fileLocationPath}";
                    Log::info("CSV Import: Documento duplicado ignorado.", ['filename' => $filename, 'path' => $fileLocationPath]);
                    $skippedCount++;
                    continue;
                }

                $documentData = [
                    'title' => $data['title'] ?? null,
                    'filename' => $filename,
                    'file_extension' => $data['file_extension'] ?? null,
                    'mime_type' => $data['mime_type'] ?? null,
                    'upload_date' => isset($data['upload_date']) ? Carbon::parse($data['upload_date']) : Carbon::now(),
                    'uploaded_by' => new ObjectId($user->id),
                    'status' => $data['status'] ?? 'active',
                ];

                $documentData['metadata'] = [];
                $documentData['metadata']['document_type'] = $data['metadata_document_type'] ?? null;
                $documentData['metadata']['document_year'] = (int) ($data['metadata_document_year'] ?? 0);

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

                if (isset($data['tags']) && !empty($data['tags'])) {
                    $documentData['tags'] = array_map('trim', explode('|', $data['tags']));
                } else {
                    $documentData['tags'] = [];
                }

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
                Log::info("CSV Import: Documento importado: {$filename}");
            }
            unlink($filePath);

            $message = "Importação concluída. Total importados: {$importedCount}, Total ignorados: {$skippedCount}.";
            if (!empty($errors)) {
                $message .= " Verifique os detalhes dos erros abaixo.";
                return redirect()->back()->with('success', $message)->with('importErrors', $errors);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error("CSV Import Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $errors[] = "Ocorreu um erro inesperado: " . $e->getMessage();
            return redirect()->back()->with('error', "Ocorreu um erro grave durante a importação. Verifique os logs do servidor.")->with('importErrors', $errors);
        }
    }
}