<?php

namespace App\Http\Controllers;

use App\Services\EnhancedNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(EnhancedNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Buscar notificações do usuário atual
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        // Para a Central de Notificações - com paginação e filtros
        $page = $request->get('page', 1);
        $perPage = min($request->get('per_page', 20), 100);
        $category = $request->get('category');
        $status = $request->get('status'); // 'read', 'unread', ou null para todos

        // Se veio da API com filtros, usar nova implementação
        if ($request->has('page') || $request->has('category') || $request->has('status')) {
            $notifications = $this->notificationService->getForUser(
                $user->id,
                $page,
                $perPage,
                $category,
                $status
            );
            
            return response()->json([
                'success' => true,
                'data' => $notifications['data'],
                'current_page' => $notifications['current_page'],
                'per_page' => $notifications['per_page'],
                'total' => $notifications['total'],
                'last_page' => $notifications['last_page']
            ]);
        }

        // Implementação antiga para compatibilidade
        $unreadOnly = $request->boolean('unread_only', false);
        $type = $request->input('type');
        $category = $request->input('category');
        $limit = $request->input('limit', 50);

        $notifications = $this->notificationService->getForUser(
            $user->id,
            $unreadOnly,
            $type,
            $category,
            $limit
        );

        $unreadCount = $this->notificationService->getUnreadCount($user->id);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'total_count' => $notifications->count()
        ]);
    }

    /**
     * Buscar apenas notificações não lidas (para polling do sininho)
     */
    public function unread(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        // Para compatibilidade com sistema anterior, buscar a mais recente
        $latestNotification = $this->notificationService->getLatestUnread($user->id);
        $unreadCount = $this->notificationService->getUnreadCount($user->id);

        return response()->json([
            'success' => true,
            'notification' => $latestNotification ? $latestNotification['message'] : null,
            'latest_notification' => $latestNotification,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Marcar notificação como lida
     */
    public function markAsRead(Request $request, $id)
    {
        $result = $this->notificationService->markAsRead($id, auth()->id());
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Notificação marcada como lida' : 'Erro ao marcar como lida'
        ]);
    }

    /**
     * Marcar notificação como não lida
     */
    public function markAsUnread(Request $request, $id)
    {
        $result = $this->notificationService->markAsUnread($id, auth()->id());
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Notificação marcada como não lida' : 'Erro ao marcar como não lida'
        ]);
    }

    /**
     * Marcar todas as notificações como lidas
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        $count = $this->notificationService->markAllAsRead($user->id);

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "Marcadas {$count} notificações como lidas"
        ]);
    }

    /**
     * Deletar notificação
     */
    public function delete(Request $request, $id)
    {
        $result = $this->notificationService->delete($id, auth()->id());
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Notificação deletada' : 'Erro ao deletar notificação'
        ]);
    }

    /**
     * Remove the specified notification from storage.
     */
    public function destroy(string $id)
    {
        $result = $this->notificationService->delete($id, auth()->id());
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Notificação excluída com sucesso' : 'Notificação não encontrada'
        ]);
    }

    /**
     * Batch mark notifications as read.
     */
    public function batchMarkAsRead(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'required|string'
        ]);

        try {
            $count = $this->notificationService->batchMarkAsRead(
                $request->notification_ids,
                auth()->id()
            );
            
            return response()->json([
                'success' => true,
                'message' => "Marcou {$count} notificação(ões) como lidas",
                'marked_count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar notificações como lidas'
            ], 500);
        }
    }

    /**
     * Batch delete notifications.
     */
    public function batchDelete(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'required|string'
        ]);

        try {
            $count = $this->notificationService->batchDelete(
                $request->notification_ids,
                auth()->id()
            );
            
            return response()->json([
                'success' => true,
                'message' => "Excluiu {$count} notificação(ões)",
                'deleted_count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir notificações'
            ], 500);
        }
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount()
    {
        $count = $this->notificationService->getUnreadCount(auth()->id());
        
        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Contar notificações não lidas
     */
    public function count(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        $count = $this->notificationService->getUnreadCount($user->id);

        return response()->json([
            'unread_count' => $count
        ]);
    }

    /**
     * Criar notificação de teste (apenas para desenvolvimento)
     */
    public function test(Request $request)
    {
        if (!app()->isLocal()) {
            return response()->json(['error' => 'Apenas em ambiente local'], 403);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        $type = $request->input('type', 'info');
        $title = $request->input('title', 'Notificação de Teste');
        $message = $request->input('message', 'Esta é uma notificação de teste');

        $notification = $this->notificationService->create(
            $user->id,
            $title,
            $message,
            $type,
            'test'
        );

        return response()->json([
            'success' => true,
            'notification' => $notification,
            'message' => 'Notificação de teste criada'
        ]);
    }
}
