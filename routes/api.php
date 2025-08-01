<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Notification routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/count', [NotificationController::class, 'unreadCount'])->name('count');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/{id}/unread', [NotificationController::class, 'markAsUnread'])->name('unread');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::post('/batch-read', [NotificationController::class, 'batchMarkAsRead'])->name('batch-read');
        Route::post('/batch-delete', [NotificationController::class, 'batchDelete'])->name('batch-delete');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        
        // Rota de teste (apenas para desenvolvimento)
        Route::post('/test', [NotificationController::class, 'test'])->name('test');
    });
});
