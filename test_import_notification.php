<?php

require_once 'vendor/autoload.php';

// Simular o ambiente Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\EnhancedNotificationService;
use App\Models\User;

try {
    echo "🧪 Testando notificação de importação...\n";

    // Buscar um usuário
    $user = User::first();
    if (!$user) {
        echo "❌ Nenhum usuário encontrado\n";
        exit(1);
    }

    echo "📝 Usuário encontrado: {$user->id}\n";

    // Criar o serviço de notificação
    $notificationService = new EnhancedNotificationService();

    // Testar a notificação de job concluído
    echo "🔔 Criando notificação de job concluído...\n";
    $notification = $notificationService->jobCompleted(
        $user->id,
        'Importação de Documentos',
        5,  // importados
        2,  // ignorados  
        3.45, // duração
        [
            'file_path' => 'temp/test.csv',
            'total_processed' => 7,
            'imported' => 5,
            'skipped' => 2
        ]
    );

    if ($notification) {
        echo "✅ Notificação criada com sucesso!\n";
        echo "   ID: {$notification->id}\n";
        echo "   Título: {$notification->title}\n";
        echo "   Mensagem: {$notification->message}\n";
        echo "   Tipo: {$notification->type}\n";
    } else {
        echo "❌ Falha ao criar notificação\n";
    }

} catch (\Exception $e) {
    echo "💥 Erro: " . $e->getMessage() . "\n";
    echo "📍 Linha: " . $e->getLine() . "\n";
    echo "📂 Arquivo: " . $e->getFile() . "\n";
}
