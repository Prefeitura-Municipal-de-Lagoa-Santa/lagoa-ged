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
        //$allMetadata = Document::query()->pluck('metadata');
        //dd($allMetadata);
        //$documentCounts = $allMetadata->map(function ($meta) {
        //    return $meta->document_type ?? 'Indefinido';
        //})->countBy();
        ////dd($documentCounts);
        ////$documents = Document::query()
        ////    ->orderBy('created_at', 'desc')
        ////    ->get();
        //return Inertia::render('Dashboard', [
        //    'documents' => $documentCounts,
        //]);

        // Usamos o método `raw()` do jenssegers/laravel-mongodb para executar uma pipeline de agregação
        $documentCounts = Document::raw(function($collection) {
            return $collection->aggregate([
                [
                    // O estágio $group agrupa os documentos pelo campo especificado e conta as ocorrências.
                    // '$metadata.document_type' acessa o campo document_type dentro do subdocumento metadata.
                    '$group' => [
                        '_id' => '$metadata.document_type', // Agrupar pelo tipo de documento
                        'count' => ['$sum' => 1]            // Contar 1 para cada documento no grupo
                    ]
                ],
                [
                    // O estágio $project é opcional, mas ajuda a formatar a saída, renomeando '_id' para 'document_type'
                    '$project' => [
                        'document_type' => '$_id', // Renomeia o campo '_id' (que é o tipo de documento) para 'document_type'
                        'count' => '$count',
                        '_id' => 0 // Remove o campo '_id' original da saída final
                    ]
                ]
            ]);
        })->pluck('count', 'document_type')->toArray(); 
        //dd($documentCounts);
        // `pluck('count', 'document_type')` transforma a coleção de resultados em um array associativo
        // onde a chave é 'document_type' e o valor é 'count'.
        // `toArray()` converte para um array PHP puro, ideal para passar ao Inertia.js.

        // Tratamento para tipos de documento nulos ou vazios (ex: '' ou null do MongoDB)
        // Se o '_id' do grupo for nulo, significa que o `document_type` era nulo ou não existia.
        $finalDocumentCounts = [];
        foreach ($documentCounts as $type => $count) {
            if (is_null($type) || $type === '') { // Verifica se o tipo é nulo ou uma string vazia
                $finalDocumentCounts['Indefinido'] = ($finalDocumentCounts['Indefinido'] ?? 0) + $count;
            } else {
                $finalDocumentCounts[$type] = $count;
            }
        }
        
        return Inertia::render('Dashboard', [
            'documents' => $finalDocumentCounts, // Passa as contagens processadas para o seu componente Vue
        ]);

    }
}
