<?php

namespace App\Services;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EnhancedNotificationService
{
    /**
     * Criar uma nova notificação
     */
    public function create(
        $userId,
        string $title,
        string $message = '',
        string $type = 'info',
        string $category = 'system',
        array $data = [],
        string $priority = 'normal',
        ?Carbon $expiresAt = null
    ): ?Notification {
        try {
            // Se não especificar expiração, usar 7 dias por padrão
            if (!$expiresAt) {
                $expiresAt = Carbon::now()->addDays(7);
            }

            $notification = Notification::create([
                'user_id' => (string) $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'category' => $category,
                'data' => $data,
                'priority' => $priority,
                'expires_at' => $expiresAt,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            Log::info('Notificação criada', [
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'type' => $type,
                'category' => $category,
                'title' => $title
            ]);

            return $notification;
        } catch (\Exception $e) {
            Log::error('Erro ao criar notificação', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Notificação de sucesso
     */
    public function success($userId, string $title, string $message = '', array $data = []): ?Notification
    {
        return $this->create($userId, $title, $message, 'success', 'user_action', $data);
    }

    /**
     * Notificação de erro
     */
    public function error($userId, string $title, string $message = '', array $data = []): ?Notification
    {
        return $this->create($userId, $title, $message, 'error', 'system', $data, 'high');
    }

    /**
     * Notificação de aviso
     */
    public function warning($userId, string $title, string $message = '', array $data = []): ?Notification
    {
        return $this->create($userId, $title, $message, 'warning', 'system', $data);
    }

    /**
     * Notificação de informação
     */
    public function info($userId, string $title, string $message = '', array $data = []): ?Notification
    {
        return $this->create($userId, $title, $message, 'info', 'system', $data);
    }

    /**
     * Notificação de job concluído
     */
    public function jobCompleted(
        $userId,
        string $jobName,
        int $successCount = 0,
        int $errorCount = 0,
        float $duration = 0,
        array $extraData = []
    ): ?Notification {
        $title = "Job Concluído: {$jobName}";
        $message = "✅ Sucesso: {$successCount} | ❌ Erros: {$errorCount} | ⏱️ Duração: " . number_format($duration, 2) . "s";
        
        $data = array_merge([
            'job_name' => $jobName,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'duration' => $duration,
        ], $extraData);

        $type = $errorCount > 0 ? 'warning' : 'success';
        $priority = $errorCount > 0 ? 'high' : 'normal';

        return $this->create($userId, $title, $message, $type, 'job', $data, $priority);
    }

    /**
     * Converter flash message para notificação
     */
    public function fromFlashMessage($userId, string $flashType, string $flashMessage): ?Notification
    {
        $typeMap = [
            'success' => 'success',
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            'message' => 'info'
        ];

        $type = $typeMap[$flashType] ?? 'info';
        $title = ucfirst($type);

        return $this->create($userId, $title, $flashMessage, $type, 'user_action');
    }

    /**
     * Buscar notificações de um usuário
     */
    public function getForUser(
        $userId,
        $pageOrUnreadOnly = 1,
        $perPageOrType = 20,
        $categoryOrCategory = null,
        $statusOrLimit = null
    ) {
        // Se for a chamada antiga (boolean, string, string, int)
        if (is_bool($pageOrUnreadOnly)) {
            return $this->getForUserLegacy($userId, $pageOrUnreadOnly, $perPageOrType, $categoryOrCategory, $statusOrLimit);
        }
        
        // Nova implementação com paginação
        $page = $pageOrUnreadOnly;
        $perPage = $perPageOrType;
        $category = $categoryOrCategory;
        $status = $statusOrLimit;
        
        $query = Notification::forUser($userId)
            ->notExpired()
            ->orderBy('created_at', 'desc');

        if ($status === 'read') {
            $query->read();
        } elseif ($status === 'unread') {
            $query->unread();
        }

        if ($category) {
            $query->byCategory($category);
        }

        $total = $query->count();
        $lastPage = ceil($total / $perPage);
        
        $notifications = $query
            ->skip(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => (string) $notification->_id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'category' => $notification->category,
                    'is_read' => $notification->isRead(),
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'time_ago' => $notification->created_at->diffForHumans(),
                ];
            });

        return [
            'data' => $notifications,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
        ];
    }

    /**
     * Implementação legacy para compatibilidade
     */
    private function getForUserLegacy(
        $userId,
        bool $unreadOnly = false,
        ?string $type = null,
        ?string $category = null,
        int $limit = 50
    ) {
        $query = Notification::forUser($userId)
            ->notExpired()
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->unread();
        }

        if ($type) {
            $query->byType($type);
        }

        if ($category) {
            $query->byCategory($category);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Contar notificações não lidas
     */
    public function getUnreadCount($userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->notExpired()
            ->count();
    }

    /**
     * Marcar notificação como lida
     */
    public function markAsRead($notificationId, $userId = null): bool
    {
        try {
            $query = Notification::where('_id', $notificationId);
            
            if ($userId) {
                $query->where('user_id', $userId);
            }
            
            $notification = $query->first();
            
            if ($notification) {
                return $notification->markAsRead();
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao marcar notificação como lida', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Marcar notificação como não lida
     */
    public function markAsUnread($notificationId, $userId = null): bool
    {
        try {
            $query = Notification::where('_id', $notificationId);
            
            if ($userId) {
                $query->where('user_id', $userId);
            }
            
            $notification = $query->first();
            
            if ($notification) {
                return $notification->markAsUnread();
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao marcar notificação como não lida', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Marcar todas as notificações como lidas para um usuário
     */
    public function markAllAsRead($userId): int
    {
        try {
            $count = Notification::forUser($userId)
                ->unread()
                ->notExpired()
                ->update(['read_at' => Carbon::now()]);

            Log::info('Notificações marcadas como lidas', [
                'user_id' => $userId,
                'count' => $count
            ]);

            return $count;
        } catch (\Exception $e) {
            Log::error('Erro ao marcar todas as notificações como lidas', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Deletar notificação
     */
    public function delete($notificationId, $userId = null): bool
    {
        try {
            $query = Notification::where('_id', $notificationId);
            
            if ($userId) {
                $query->where('user_id', $userId);
            }
            
            $notification = $query->first();
            
            if ($notification) {
                return $notification->delete();
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao deletar notificação', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Marcar múltiplas notificações como lidas
     */
    public function batchMarkAsRead(array $notificationIds, $userId = null): int
    {
        try {
            $query = Notification::whereIn('_id', $notificationIds);
            
            if ($userId) {
                $query->where('user_id', $userId);
            }
            
            return $query->update(['read_at' => Carbon::now()]);
        } catch (\Exception $e) {
            Log::error('Erro ao marcar notificações como lidas em lote', [
                'notification_ids' => $notificationIds,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Deletar múltiplas notificações
     */
    public function batchDelete(array $notificationIds, $userId = null): int
    {
        try {
            $query = Notification::whereIn('_id', $notificationIds);
            
            if ($userId) {
                $query->where('user_id', $userId);
            }
            
            return $query->delete();
        } catch (\Exception $e) {
            Log::error('Erro ao deletar notificações em lote', [
                'notification_ids' => $notificationIds,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Limpeza automática de notificações expiradas
     */
    public function cleanup(): array
    {
        try {
            $expiredCount = Notification::cleanupExpired();
            $oldCount = Notification::cleanupOld(7);

            Log::info('Limpeza de notificações concluída', [
                'expired_deleted' => $expiredCount,
                'old_deleted' => $oldCount
            ]);

            return [
                'expired_deleted' => $expiredCount,
                'old_deleted' => $oldCount,
                'total_deleted' => $expiredCount + $oldCount
            ];
        } catch (\Exception $e) {
            Log::error('Erro na limpeza de notificações', [
                'error' => $e->getMessage()
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Buscar uma notificação não lida mais recente (para compatibilidade com sistema anterior)
     */
    public function getLatestUnread($userId): ?array
    {
        $notification = Notification::forUser($userId)
            ->unread()
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->first();

        if ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'category' => $notification->category,
                'data' => $notification->data,
                'created_at' => $notification->created_at->toISOString(),
                'is_read' => false
            ];
        }

        return null;
    }
}
