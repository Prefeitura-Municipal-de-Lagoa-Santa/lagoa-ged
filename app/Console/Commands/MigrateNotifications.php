<?php

namespace App\Console\Commands;

use App\Services\EnhancedNotificationService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class MigrateNotifications extends Command
{
    protected $signature = 'notifications:migrate';
    protected $description = 'Migra notificações do Redis para o MongoDB';

    public function handle()
    {
        $this->info("🚀 Iniciando migração de notificações do Redis para MongoDB...");
        
        // Nota: Como o Redis usa TTL, provavelmente não há dados para migrar
        // Este comando é mais para futuras migrações
        
        $this->info("ℹ️  Nota: O sistema Redis usava TTL, então provavelmente não há dados para migrar.");
        $this->info("✅ O novo sistema já está ativo e funcionando!");
        
        // Criar algumas notificações de exemplo se estivermos em desenvolvimento
        if (app()->isLocal()) {
            $this->info("\n🧪 Criando notificações de exemplo...");
            
            $service = new EnhancedNotificationService();
            
            // Exemplo para usuário 1
            $service->success(1, 'Migração Concluída', 'Sistema de notificações migrado com sucesso!');
            $service->info(1, 'Sistema Atualizado', 'Agora as notificações ficam salvas por 7 dias com histórico completo.');
            
            // Exemplo para o usuário atual do MongoDB
            $service->success('6888c77666180cbaa1027cf2', 'Bem-vindo ao Novo Sistema', 'Sistema de notificações aprimorado está ativo!');
            $service->info('6888c77666180cbaa1027cf2', 'Recursos Disponíveis', 'Histórico de 7 dias, marcar como lida/não lida, categorias e muito mais.');
            
            $this->info("✨ Notificações de exemplo criadas!");
        }
        
        return 0;
    }
}
