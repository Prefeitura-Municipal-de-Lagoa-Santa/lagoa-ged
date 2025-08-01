<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationService
{
    private $redis;
    private $defaultTtl;
    
    public function __construct()
    {
        $this->redis = Redis::connection('notifications');
        $this->defaultTtl = 1800; // 30 minutos em segundos
    }
    
    /**
     * Adiciona uma notificação para um usuário
     */
    public function addNotification($userId, $message, $type = 'info', $ttlMinutes = 10)
    {
        try {
            $key = $this->getNotificationKey($userId);
            $timestamp = now()->toISOString();
            
            $notification = [
                'id' => uniqid(),
                'message' => $this->formatMessage($message, $type),
                'type' => $type,
                'created_at' => $timestamp,
                'user_id' => $userId
            ];
            
            // Salvar no Redis com TTL
            $ttlSeconds = $ttlMinutes * 60;
            $this->redis->setex($key, $ttlSeconds, json_encode($notification));
            
            Log::info('Notificação Redis criada', [
                'user_id' => $userId,
                'type' => $type,
                'key' => $key,
                'ttl_seconds' => $ttlSeconds,
                'message' => $notification['message']
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao adicionar notificação Redis', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Recupera a notificação de um usuário
     */
    public function getNotification($userId)
    {
        try {
            $key = $this->getNotificationKey($userId);
            $data = $this->redis->get($key);
            
            Log::info('=== DEBUG GET NOTIFICATION ===', [
                'user_id' => $userId,
                'key' => $key,
                'data_exists' => $data !== null,
                'data_length' => $data ? strlen($data) : 0,
                'raw_data' => $data
            ]);
            
            if ($data) {
                $notification = json_decode($data, true);
                Log::info('Notificação Redis encontrada', [
                    'user_id' => $userId,
                    'key' => $key,
                    'notification_id' => $notification['id'] ?? 'unknown',
                    'message_length' => strlen($notification['message'] ?? '')
                ]);
                return $notification;
            }
            
            Log::info('Nenhuma notificação Redis encontrada', [
                'user_id' => $userId,
                'key' => $key
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar notificação Redis', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Remove a notificação de um usuário (marcar como lida)
     */
    public function markAsRead($userId)
    {
        try {
            $key = $this->getNotificationKey($userId);
            $result = $this->redis->del($key);
            
            Log::info('Notificação Redis marcada como lida', [
                'user_id' => $userId,
                'key' => $key,
                'deleted' => $result > 0
            ]);
            
            return $result > 0;
        } catch (\Exception $e) {
            Log::error('Erro ao marcar notificação como lida', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Limpa notificações expiradas (executado automaticamente pelo TTL do Redis)
     * Este método é opcional, pois o Redis já remove automaticamente
     */
    public function cleanupExpiredNotifications()
    {
        Log::info('Redis TTL cuida automaticamente da limpeza de notificações expiradas');
        return true;
    }
    
    /**
     * Conta quantas notificações existem no sistema
     */
    public function getNotificationCount()
    {
        try {
            $pattern = 'notifications:user:*';
            $keys = $this->redis->keys($pattern);
            return count($keys);
        } catch (\Exception $e) {
            Log::error('Erro ao contar notificações', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Métodos de conveniência para diferentes tipos
     */
    public function success($userId, $message, $ttlMinutes = 30)
    {
        return $this->addNotification($userId, $message, 'success', $ttlMinutes);
    }
    
    public function error($userId, $message, $ttlMinutes = 60)
    {
        return $this->addNotification($userId, $message, 'error', $ttlMinutes);
    }
    
    public function warning($userId, $message, $ttlMinutes = 45)
    {
        return $this->addNotification($userId, $message, 'warning', $ttlMinutes);
    }
    
    public function info($userId, $message, $ttlMinutes = 30)
    {
        return $this->addNotification($userId, $message, 'info', $ttlMinutes);
    }
    
    /**
     * Cria notificação específica para jobs
     */
    public function jobCompleted($userId, $successCount, $errorCount, $duration, $jobType = 'Processamento')
    {
        if ($errorCount === 0) {
            $message = "{$jobType} Concluído!";
            return $this->success($userId, $message);
        } elseif ($successCount === 0) {
            $message = "Falha no {$jobType}. {$errorCount} item(s) falharam. Verifique os logs para mais detalhes.";
            return $this->error($userId, $message);
        } else {
            $message = "{$jobType} Parcialmente Concluído. {$successCount} item(s) processados com sucesso, {$errorCount} falharam.";
            return $this->warning($userId, $message);
        }
    }
    
    /**
     * Gera a chave Redis para notificação do usuário
     */
    private function getNotificationKey($userId)
    {
        return "notifications:user:{$userId}";
    }
    
    /**
     * Formatar mensagem baseada no tipo
     */
    private function formatMessage($message, $type)
    {
        $icons = [
            'success' => '🎉',
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            'job' => '⚙️'
        ];
        
        $icon = $icons[$type] ?? 'ℹ️';
        $timestamp = now()->format('d/m/Y H:i:s');
        
        return "{$icon} <strong>{$message}</strong><br>⏱️ {$timestamp}";
    }
}
