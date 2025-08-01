<?php

namespace App\Console\Commands;

use App\Services\EnhancedNotificationService;
use Illuminate\Console\Command;

class CleanupNotifications extends Command
{
    protected $signature = 'notifications:cleanup {--days=7 : Número de dias para manter notificações}';
    protected $description = 'Limpa notificações antigas e expiradas';

    public function handle()
    {
        $days = $this->option('days');
        
        $this->info("Iniciando limpeza de notificações...");
        $this->info("Mantendo notificações dos últimos {$days} dias");
        
        $service = new EnhancedNotificationService();
        $result = $service->cleanup();
        
        if (isset($result['error'])) {
            $this->error("Erro na limpeza: " . $result['error']);
            return 1;
        }
        
        $this->info("✅ Limpeza concluída:");
        $this->line("   - Notificações expiradas removidas: {$result['expired_deleted']}");
        $this->line("   - Notificações antigas removidas: {$result['old_deleted']}");
        $this->line("   - Total removidas: {$result['total_deleted']}");
        
        return 0;
    }
}
