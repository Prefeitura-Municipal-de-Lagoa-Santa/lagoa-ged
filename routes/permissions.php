<?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get(uri: '/permissions/groups', action: [GroupController::class, 'index'])->name(name: 'permissions.groups');
Route::get(uri: '/permissions/users', action: [UserController::class, 'index'])->name(name: 'permissions.users');
//Route::get('/documents/{id}/show', [DocumentController::class, 'show'])->name('documents.show');
//Route::get('/documents/{document}/view', [DocumentController::class, 'view'])->name('documents.view');
//Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
// Permissões específicas
//Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])
  //  ->name('users.permissions');
//Route::post('/users/{user}/permissions', [UserController::class, 'updatePermissions'])
  //  ->name('users.permissions.update');