<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Services\NotificationService;

$notificationService = new NotificationService();

echo "Testando NotificationService...\n";

$result = $notificationService->jobCompleted('6888c77666180cbaa1027cf2', 5, 0, 10, 'Teste Manual');

echo "Resultado: " . ($result ? 'Sucesso' : 'Falha') . "\n";

// Verificar se foi criada
$notification = $notificationService->getNotification('6888c77666180cbaa1027cf2');
echo "Notificação encontrada: " . ($notification ? 'Sim' : 'Não') . "\n";
if ($notification) {
    echo "Mensagem: " . $notification['message'] . "\n";
}
