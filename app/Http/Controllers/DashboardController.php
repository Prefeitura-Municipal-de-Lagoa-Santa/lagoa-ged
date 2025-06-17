<?php

namespace App\Http\Controllers;

use App\Models\Document; // Seu Model Document (MongoDB)
use Illuminate\Http\Request;
use Inertia\Inertia; // Importe o Inertia
use Illuminate\Support\Facades\Auth; // Para obter o usuário logado
use Storage;

class DashboardController extends Controller
{
    public function index()
    {
        $allMetadata = Document::query()->pluck('metadata');
        $documentCounts = $allMetadata->map(function ($meta) {
            return $meta->document_type ?? 'Indefinido';
        })->countBy();
        //dd($documentCounts);
        //$documents = Document::query()
        //    ->orderBy('created_at', 'desc')
        //    ->get();
        return Inertia::render('Dashboard', [
            'documents' => $documentCounts,
        ]);
    }
}
