<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/settings.php';
require __DIR__.'/seguridad.php';
require __DIR__.'/ingenierias.php';
require __DIR__.'/archivos.php';
require __DIR__.'/notificaciones.php';

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});
