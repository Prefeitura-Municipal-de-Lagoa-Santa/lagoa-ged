<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportDocumentRequest;
use App\Models\Document; // Seu Model Document (MongoDB)
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia; // Importe o Inertia
use Illuminate\Support\Facades\Auth; // Para obter o usuário logado
use Log;
use MongoDB\BSON\ObjectId;
use SplFileObject;
use Storage;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::query()
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return Inertia::render('documents/index', [
            'documents' => $documents,
            'filters' => [],
        ]);
    }

    //exibe documentos
    public function view(Document $document)
    {
        //dd($document->file_location['path']);
        $filePath = $document->file_location['path'];
        //dd($filePath);

        if (!Storage::disk('samba')->exists($filePath)) {
            abort(404, 'Arquivo não encontrado no compartilhamento.');
        }

        $fileName = $document->filename ?? basename($filePath);
        $mimeType = $document->mime_type ?? Storage::disk('samba')->mimeType($filePath);
        //dd($fileName, $mimeType);

        // Este é o comando chave: ele retorna o arquivo com os headers corretos para exibição.
        return Storage::disk('samba')->response($filePath, $fileName,[
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function show(Document $document)
    {
        //dd($document);// Passa os dados do documento para a view.
        return Inertia::render('documents/show', [
            'document' => $document
        ]);
    }

    public function import()
    {

        $groups = Group::whereNot('name', 'ADMINISTRADORES')->get(['_id', 'name']);

        return Inertia::render('documents/import', [
            'groups' => $groups,
        ]);
    }
    public function processImport(ImportDocumentRequest $request)
    {
        //$validateData=$request->validated();
        //dd($validateData);
        $file = $request->file('csv_file');
        $filePath = $file->getPathname();

        // Recebe os IDs dos grupos como STRINGS do request
        $readGroupIdsInput = array_filter($request->input('read_group_ids', []));
        $writeGroupIdsInput = array_filter($request->input('write_group_ids', []));

        // CONVERTE AS STRINGS DE IDS PARA INSTÂNCIAS DE Jenssegers\Mongodb\Eloquent\ObjectId
        $readGroupIds = collect($readGroupIdsInput)
                                ->map(function ($id) {
                                    return new ObjectId($id); // <-- Usando ObjectId do Jenssegers
                                })->toArray();
        $writeGroupIds = collect($writeGroupIdsInput)
                                ->map(function ($id) {
                                    return new ObjectId($id); // <-- Usando ObjectId do Jenssegers
                                })->toArray();

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
                //$title = $data['title'] ?? null;
                //dd($filename, $fileLocationPath);

                if (!$filename || !$fileLocationPath) {
                    $errors[] = "Linha ignorada por falta de 'filename' ou 'file_location_path': " . json_encode($data);
                    Log::warning("CSV Import: Linha ignorada por falta de 'filename' ou 'file_location_path'.", ['data' => $data]);
                    $skippedCount++;
                    continue;
                }

                //$docs = Document::limit(5)->get();
                //dd($docs);
                $existingDocument = Document::where('filename', $filename)
                    ->where('file_location.path', $fileLocationPath)
                    ->first();
                //dd($existingDocument);
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
                    //'file_size' => (int) ($data['file_size'] ?? 0),
                    'upload_date' => isset($data['upload_date']) ? Carbon::parse($data['upload_date']) : Carbon::now(),
                    'uploaded_by' => auth()->id() ?? 1,
                    'status' => $data['status'] ?? 'active',
                ];

                $documentData['metadata'] = [];

                $documentData['metadata']['document_type'] = $data['metadata_document_type'] ?? null;
                $documentData['metadata']['document_year'] = (int) ($data['metadata_document_year'] ?? 0);

                // =====================================================================
                // LÓGICA PARA METADADOS DINÂMICOS
                // =====================================================================
                foreach ($data as $csvHeader => $value) {
                    // Verifica se o cabeçalho começa com 'metadata_' e não é um dos campos já tratados
                    if (
                        str_starts_with($csvHeader, 'metadata_') &&
                        $csvHeader !== 'metadata_document_type' && // Evita duplicar/sobrescrever se já tratado
                        $csvHeader !== 'metadata_document_year'
                    )    // Evita duplicar/sobrescrever se já tratado
                    {
                        // Extrai o nome real do campo de metadado (remover 'metadata_')
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

                //$doc = new Document($documentData);
                //dd($doc->getAttributes());


                Document::create($documentData);
                $importedCount++;
                Log::info("CSV Import: Documento importado: {$filename}");
            }
            //dd($errors);
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
