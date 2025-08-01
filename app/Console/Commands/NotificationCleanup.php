<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class NotificationCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup {--test : Criar notificação de teste} {--count : Mostrar contagem de notificações} {--user= : ID do usuário para teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa notificações expiradas e oferece comandos de teste';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $notificationService = new NotificationService();
        
        if ($this->option('count')) {
            $count = $notificationService->getNotificationCount();
            $this->info("Total de notificações ativas no Redis: {$count}");
            return;
        }
        
        if ($this->option('test')) {
            $userId = $this->option('user') ?? '6888c77666180cbaa1027cf2';
            
            $this->info("Criando notificação de teste para usuário: {$userId}");
            
            // Testar diferentes métodos
            $this->info("Testando método success...");
            $result1 = $notificationService->success($userId, 'Teste método success', 5);
            $this->info("Resultado success: " . ($result1 ? '✅' : '❌'));
            
            $this->info("Testando método jobCompleted...");
            $result2 = $notificationService->jobCompleted($userId, 5, 0, 10, 'Teste jobCompleted');
            $this->info("Resultado jobCompleted: " . ($result2 ? '✅' : '❌'));
            
            return;
        }
        
        // Limpeza (o Redis faz automaticamente via TTL)
        $notificationService->cleanupExpiredNotifications();
        $this->info('✅ Redis gerencia automaticamente a limpeza via TTL.');
        
        $count = $notificationService->getNotificationCount();
        $this->info("Notificações ativas restantes: {$count}");
    }
}
