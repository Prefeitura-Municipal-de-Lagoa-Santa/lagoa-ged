<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

/*Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');*/

Route::middleware(['auth'])->group(function () {
    Route::redirect('/', '/dashboard');

    Route::get('/dashboard',  [DashboardController::class, 'index'])->middleware(['verified'])->name( 'dashboard');
    //Route::get('/dashboard', function () {
    //    return Inertia::render('Dashboard');
    //})->middleware(['verified'])->name('dashboard');
    
    require __DIR__ . '/documents.php';
    require __DIR__ . '/permissions.php';
    require __DIR__ . '/notifications.php';
});
//require __DIR__ . '/settings.php';


require __DIR__ . '/auth.php';
