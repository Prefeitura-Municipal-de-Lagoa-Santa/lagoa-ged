<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');*/

Route::middleware(['auth'])->group(function () {
     Route::redirect('/', '/dashboard');/*function (): RedirectResponse {
        // Se o usuário chegou até aqui, o middleware 'auth' já confirmou que ele está logado.
        // Portanto, redirecionamos para o dashboard.
        return redirect()->route('dashboard');
    })->name('home');*/
    
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    require __DIR__ . '/settings.php';
});

require __DIR__ . '/auth.php';