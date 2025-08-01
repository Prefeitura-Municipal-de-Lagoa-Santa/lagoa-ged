<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

Route::get('/documents',  [DocumentController::class, 'index'])->name( 'documents.index');
Route::get('/documents/{document}/show', [DocumentController::class, 'show'])->name('documents.show');
Route::get('/documents/{document}/view', [DocumentController::class, 'view'])->name('documents.view');
Route::get('/documents/import', [DocumentController::class, 'import'])->name('documents.import');
Route::post('/documents/import', [DocumentController::class, 'processImport'])->name('documents.import.process');
Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
Route::get('documents/batch-permissions', [DocumentController::class, 'batchPermissions'])->name('documents.batch-permissions');
Route::post('documents/batch-permissions', [DocumentController::class, 'batchPermissionsUpdate'])->name('documents.batch-permissions');
Route::get('documents/notifications', [DocumentController::class, 'getNotifications'])->name('documents.notifications');
//Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
// Permissões específicas
//Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])
  //  ->name('users.permissions');
//Route::post('/users/{user}/permissions', [UserController::class, 'updatePermissions'])
  //  ->name('users.permissions.update');