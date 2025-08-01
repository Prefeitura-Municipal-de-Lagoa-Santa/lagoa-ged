<?php

namespace App\Console\Commands;

use App\Services\EnhancedNotificationService;
use Illuminate\Console\Command;

class TestNotification extends Command
{
    protected $signature = 'test:notification 
                            {user_id=1 : ID do usuário} 
                            {title=Teste de notificação : Título da notificação}
                            {--message=Esta é uma notificação de teste : Mensagem da notificação}
                            {--type=success : Tipo da notificação (success, error, warning, info)}
                            {--category=test : Categoria da notificação}';
    protected $description = 'Testa o novo sistema de notificações';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $title = $this->argument('title');
        $message = $this->option('message');
        $type = $this->option('type');
        $category = $this->option('category');
        
        $this->info("🔔 Criando notificação para usuário {$userId}...");
        
        $service = new EnhancedNotificationService();
        $notification = $service->create($userId, $title, $message, $type, $category);
        
        if ($notification) {
            $this->info("✅ Notificação criada com sucesso!");
            $this->line("   ID: {$notification->id}");
            $this->line("   Título: {$notification->title}");
            $this->line("   Tipo: {$notification->type}");
            $this->line("   Categoria: {$notification->category}");
            $this->line("   Expira em: " . $notification->expires_at->format('d/m/Y H:i:s'));
        } else {
            $this->error("❌ Falha ao criar notificação");
            return 1;
        }
        
        // Tentar recuperar
        $this->info("\n📖 Buscando notificações do usuário...");
        $notifications = $service->getForUser($userId, false, null, null, 10);
        $unreadCount = $service->getUnreadCount($userId);
        
        $this->info("✅ Encontradas {$notifications->count()} notificações");
        $this->info("📬 Não lidas: {$unreadCount}");
        
        if ($notifications->count() > 0) {
            $this->line("\n📋 Últimas notificações:");
            foreach ($notifications->take(5) as $notif) {
                $status = $notif->isRead() ? '✓' : '●';
                $this->line("   {$status} [{$notif->type}] {$notif->title} - " . $notif->created_at->diffForHumans());
            }
        }
        
        return 0;
    }
}
