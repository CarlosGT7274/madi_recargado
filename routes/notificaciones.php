<?php

use App\Http\Controllers\NotificacionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('notificaciones')
    ->name('notificaciones.')
    ->group(function () {
        Route::patch('/{notificacion}/leida', [NotificacionController::class, 'marcarLeida'])->name('marcar-leida');
        Route::patch('/leidas', [NotificacionController::class, 'marcarTodasLeidas'])->name('marcar-todas-leidas');
    });
