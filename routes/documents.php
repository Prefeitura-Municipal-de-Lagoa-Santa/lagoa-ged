<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/documents',  [DocumentController::class, 'index'])->name( 'documents.index');
Route::get('/documents/{id}/show', [DocumentController::class, 'show'])->name('documents.show');
Route::get('/documents/{document}/view', [DocumentController::class, 'view'])->name('documents.view');
//Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
// Permissões específicas
//Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])
  //  ->name('users.permissions');
//Route::post('/users/{user}/permissions', [UserController::class, 'updatePermissions'])
  //  ->name('users.permissions.update');