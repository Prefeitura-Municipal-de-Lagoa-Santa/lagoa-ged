<?php

require_once 'vendor/autoload.php';
use MongoDB\BSON\ObjectId;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Simular o que o ImportDocumentsJob faz
$groupId = "6888c766f7ca89e5140ebce4"; // ID do grupo ADLP

echo "Testando conversão de ID: $groupId\n";

// Método do ImportDocumentsJob
$readGroupIds = collect(array_filter([$groupId]))
    ->map(fn($id) => new ObjectId($id))
    ->toArray();

$writeGroupIds = collect(array_filter([$groupId]))
    ->map(fn($id) => new ObjectId($id))
    ->toArray();

echo "ReadGroupIds após conversão:\n";
var_dump($readGroupIds);

echo "\nWriteGroupIds após conversão:\n";
var_dump($writeGroupIds);

// Testar salvamento
$document = App\Models\Document::first();
if ($document) {
    echo "\nTeste de atualização de permissões...\n";
    echo "Permissões antes:\n";
    var_dump($document->permissions);
    
    $permissions = $document->permissions ?? [];
    $permissions['read_group_ids'] = $readGroupIds;
    $permissions['write_group_ids'] = $writeGroupIds;
    
    $document->permissions = $permissions;
    $result = $document->save();
    
    echo "Resultado do save: " . ($result ? "true" : "false") . "\n";
    
    // Buscar novamente para ver como foi salvo
    $freshDocument = App\Models\Document::find($document->id);
    echo "Permissões após save:\n";
    var_dump($freshDocument->permissions);
}
