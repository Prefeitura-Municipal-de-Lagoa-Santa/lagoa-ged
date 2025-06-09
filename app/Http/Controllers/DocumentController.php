<?php

namespace App\Http\Controllers;

use App\Models\Document; // Seu Model Document (MongoDB)
use Illuminate\Http\Request;
use Inertia\Inertia; // Importe o Inertia
use Illuminate\Support\Facades\Auth; // Para obter o usuário logado

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::query()
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return Inertia::render('documents/index', [
            'documents'=>$documents,
            'filters' =>[],
        ]);
    }

    public function show($document)
    {
        $documents = Document::query()
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return Inertia::render('documents/show', [
            'document'=>$documents,
            'filters' =>[],
        ]);
    }
}
