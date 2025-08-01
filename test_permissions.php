<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Testar a estrutura das permissões
$document = App\Models\Document::first();

if ($document) {
    echo "Documento encontrado: " . $document->title . "\n";
    echo "Permissões atuais:\n";
    var_dump($document->permissions);
    
    // Testar um grupo
    $group = App\Models\Group::first();
    if ($group) {
        echo "\nGrupo encontrado: " . $group->name . " (ID: " . $group->id . ")\n";
        echo "Tipo do ID: " . gettype($group->id) . "\n";
    }
} else {
    echo "Nenhum documento encontrado\n";
}
