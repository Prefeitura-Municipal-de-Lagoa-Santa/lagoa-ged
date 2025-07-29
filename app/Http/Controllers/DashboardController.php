<?php

namespace App\Http\Controllers;

use App\Models\Document; // Seu Model Document (MongoDB)
use Illuminate\Http\Request;
use Inertia\Inertia; // Importe o Inertia
use Illuminate\Support\Facades\Auth; // Para obter o usuário logado
use Illuminate\Support\Facades\Log;
class DashboardController extends Controller
{
    public function index()
    {
        // Tipos de documentos que compõem ADLP
        $tiposADLP = ['DECRETO', 'ATO', 'LEI', 'PORTARIA'];

        // Agregação para contagem por tipo de documento E para somar ADLP
        $aggregationResult = Document::raw(function($collection) use ($tiposADLP) {
            return $collection->aggregate([
                // Primeiro, agrupa por tipo de documento para obter as contagens individuais
                [
                    '$group' => [
                        '_id' => '$metadata.document_type',
                        'count' => ['$sum' => 1]
                    ]
                ],
                // Agora, processa os resultados para formatar e calcular o total ADLP
                [
                    '$facet' => [
                        'documentCounts' => [
                            [
                                '$project' => [
                                    'document_type' => '$_id',
                                    'count' => '$count',
                                    '_id' => 0
                                ]
                            ]
                        ],
                        'adlpTotal' => [
                            [
                                // Filtra apenas os tipos ADLP para esta parte do facet
                                '$match' => [
                                    '_id' => ['$in' => $tiposADLP]
                                ]
                            ],
                            [
                                // Soma as contagens dos tipos ADLP
                                '$group' => [
                                    '_id' => null, // Agrupa tudo em um único resultado
                                    'total' => ['$sum' => '$count']
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
        })->toArray(); // Converte o resultado da agregação para um array

        // Processa as contagens de documentos individuais
        $finalDocumentCounts = [];
        $documentCountsRaw = $aggregationResult[0]['documentCounts'] ?? [];
        foreach ($documentCountsRaw as $item) {
            $type = $item['document_type'];
            $count = $item['count'];
            if (is_null($type) || $type === '') {
                $finalDocumentCounts['Indefinido'] = ($finalDocumentCounts['Indefinido'] ?? 0) + $count;
            } else {
                $finalDocumentCounts[$type] = $count;
            }
        }

        // Processa o total de ADLP
        $totalADLP = 0;
        if (!empty($aggregationResult[0]['adlpTotal'])) {
            $totalADLP = $aggregationResult[0]['adlpTotal'][0]['total'] ?? 0;
        }

        return Inertia::render('Dashboard', [
            'documents' => $finalDocumentCounts,
            'totalADLP' => $totalADLP, // Passa a soma de ADLP separadamente
        ]);
    }
}
