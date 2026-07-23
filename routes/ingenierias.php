<?php

use App\Http\Controllers\ProyectoController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('permiso:'.Accion::READ)
        ->name('ingenierias.proyectos.index')
        ->get('ingenierias/proyectos', [ProyectoController::class, 'index']);
});
