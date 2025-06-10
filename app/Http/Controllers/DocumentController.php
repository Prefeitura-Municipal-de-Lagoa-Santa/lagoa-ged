<?php

namespace App\Http\Controllers;

use App\Models\Document; // Seu Model Document (MongoDB)
use Illuminate\Http\Request;
use Inertia\Inertia; // Importe o Inertia
use Illuminate\Support\Facades\Auth; // Para obter o usuário logado
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
        $filePath = $document->file_location->path;
       
        if (!Storage::disk('samba')->exists($filePath)) {
            abort(404, 'Arquivo não encontrado no compartilhamento.');
        }

        // Este é o comando chave: ele retorna o arquivo com os headers corretos para exibição.
        return Storage::disk('samba')->response($filePath);
    }

    public function show(Document $id)
    {
       // Passa os dados do documento para a view.
        return Inertia::render('documents/show', [
            'document' => $id
        ]);
    }
}
