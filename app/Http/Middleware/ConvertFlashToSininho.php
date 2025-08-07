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
        
        $path = $request->path();
        
        // Apenas ignorar rotas de API, mas permitir requisições Inertia
        if (str_starts_with($path, 'api/')) {
            return $response;
        }
        
        // Log para debug - sempre logar
        //\Log::info('ConvertFlashToSininho: Middleware executado', [
        //    'path' => $path,
        //    'user_id' => (string) $user->id,
        //    'method' => $request->method(),
        //    'is_ajax' => $request->ajax(),
        //    'wants_json' => $request->wantsJson(),
        //    'expects_json' => $request->expectsJson(),
        //    'inertia' => $request->header('X-Inertia')
        //]);
        
        // Só processar se houver mensagens flash ativas
        $hasFlashMessages = false;
        $flashTypes = ['success', 'error', 'warning', 'info', 'message'];
        foreach ($flashTypes as $type) {
            if (Session::has($type)) {
                $hasFlashMessages = true;
                break;
            }
        }
        
        if (!$hasFlashMessages) {
            //\Log::info('ConvertFlashToSininho: Nenhuma mensagem flash encontrada', [
            //    'session_all' => Session::all()
            //]);
            return $response;
        }
        
        \Log::info('ConvertFlashToSininho: Mensagens flash encontradas', [
            'session_flash' => array_filter(Session::all(), function($key) {
                return in_array($key, ['success', 'error', 'warning', 'info', 'message']);
            }, ARRAY_FILTER_USE_KEY)
        ]);
        
        // Capturar mensagens flash comuns
        foreach ($flashTypes as $type) {
            if (Session::has($type)) {
                $message = Session::get($type);
                if (!empty($message)) {
                    // Verificar se já existe uma notificação similar recente (últimos 10 segundos)
                    $existingNotification = \App\Models\Notification::forUser($user->id)
                        ->where('message', $message)
                        ->where('type', $type === 'message' ? 'info' : $type)
                        ->where('created_at', '>=', Carbon::now()->subSeconds(10))
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
