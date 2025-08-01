<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // API de notificações
    Route::prefix('api/notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']); // Listar notificações
        Route::get('/unread', [NotificationController::class, 'unread']); // Notificações não lidas
        Route::get('/count', [NotificationController::class, 'count']); // Contar não lidas
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']); // Marcar como lida
        Route::post('/{id}/unread', [NotificationController::class, 'markAsUnread']); // Marcar como não lida
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']); // Marcar todas como lidas
        Route::post('/batch-read', [NotificationController::class, 'batchMarkAsRead']); // Marcar em lote como lidas
        Route::post('/batch-delete', [NotificationController::class, 'batchDelete']); // Deletar em lote
        Route::delete('/{id}', [NotificationController::class, 'destroy']); // Deletar notificação
        
        // Rota de teste apenas em desenvolvimento
        if (app()->isLocal()) {
            Route::post('/test', [NotificationController::class, 'test']);
        }
    });
    
    // Página de teste de notificações (apenas em desenvolvimento)
    if (app()->isLocal()) {
        Route::get('/test-notifications', function () {
            return view('test-notifications');
        })->name('test-notifications');
    }
});
