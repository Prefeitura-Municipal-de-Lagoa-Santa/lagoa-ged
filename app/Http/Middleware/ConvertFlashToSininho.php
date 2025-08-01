<?php

namespace App\Http\Middleware;

use App\Services\EnhancedNotificationService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ConvertFlashToSininho
{
    protected $notificationService;

    public function __construct(EnhancedNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Só processar se o usuário estiver autenticado
        $user = Auth::user();
        if (!$user) {
            return $response;
        }
        
        // Não processar rotas de API, notificações, ou requisições AJAX
        $path = $request->path();
        if (str_starts_with($path, 'api/') || 
            str_contains($path, 'notifications') ||
            $request->ajax() ||
            $request->wantsJson() ||
            $request->expectsJson()) {
            return $response;
        }
        
        // Só processar se houver mensagens flash ativas
        $hasFlashMessages = false;
        foreach (['success', 'error', 'warning', 'info', 'message'] as $type) {
            if (Session::has($type)) {
                $hasFlashMessages = true;
                break;
            }
        }
        
        if (!$hasFlashMessages) {
            return $response;
        }
        
        // Capturar mensagens flash comuns
        foreach (['success', 'error', 'warning', 'info', 'message'] as $type) {
            if (Session::has($type)) {
                $message = Session::get($type);
                if (!empty($message)) {
                    // Verificar se já existe uma notificação similar recente (últimos 30 segundos)
                    $existingNotification = \App\Models\Notification::forUser($user->id)
                        ->where('message', $message)
                        ->where('type', $type === 'message' ? 'info' : $type)
                        ->where('created_at', '>=', Carbon::now()->subSeconds(30))
                        ->first();
                    
                    if ($existingNotification) {
                        \Log::info('ConvertFlashToSininho: Notificação duplicada ignorada', [
                            'type' => $type,
                            'message' => $message,
                            'existing_id' => $existingNotification->id
                        ]);
                        continue;
                    }
                    
                    \Log::info('ConvertFlashToSininho: Flash message encontrada', [
                        'type' => $type,
                        'message' => $message,
                        'user_id' => (string) $user->id,
                        'path' => $path
                    ]);
                    
                    // Usar o novo serviço
                    $notification = $this->notificationService->fromFlashMessage($user->id, $type, $message);
                    
                    \Log::info('ConvertFlashToSininho: Notificação criada', [
                        'notification_id' => $notification ? $notification->id : null,
                        'success' => (bool) $notification,
                        'type' => $type,
                        'message' => $message
                    ]);
                }
            }
        }
        
        return $response;
    }
}
