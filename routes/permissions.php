<?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Rotas Groups
Route::get(uri: '/permissions/groups', action: [GroupController::class, 'index'])->name(name: 'groups.index');
Route::get(uri: '/permissions/groups/{group}/edit', action: [GroupController::class, 'edit'])->name(name: 'groups.edit');
Route::put('/permissions/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
Route::get('/permissions/groups/create', [GroupController::class, 'create'])->name('groups.create');
Route::post('/permissions/groups/store', [GroupController::class, 'store'])->name('groups.store');
Route::post('/permissions/groups/{group}/destroy', [GroupController::class, 'destroy'])->name('groups.destroy');

// Rotas Users
Route::get(uri: '/permissions/users', action: [UserController::class, 'index'])->name(name: 'users.index');
Route::get(uri: '/permissions/users/{user}/edit', action: [UserController::class, 'edit'])->name(name: 'users.edit');
Route::put('/permissions/users/{user}', [UserController::class, 'update'])->name('users.update');
Route::get('/permissions/users/create', [UserController::class, 'create'])->name('users.create');
Route::post('/permissions/users/store', [UserController::class, 'store'])->name('users.store');

// Permissões específicas
//Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])users
  //  ->name('users.permissions');
//Route::post('/users/{user}/permissions', [UserController::class, 'updatePermissions'])
  //  ->name('users.permissions.update');