<?php

use App\Http\Controllers\Ingenierias\LevantamientoController;
use App\Http\Controllers\Ingenierias\PlantaController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Grupo de Plantas
    Route::name('ingenierias.plantas.')
        ->prefix('ingenierias/plantas')
        ->middleware('permiso:' . Accion::READ)
        ->group(function () {
            Route::get('/', [PlantaController::class, 'index'])->name('index');
            Route::get('/{planta}', [PlantaController::class, 'show'])->name('show');
            Route::post('/', [PlantaController::class, 'store'])->name('store');
            Route::put('/{planta}', [PlantaController::class, 'update'])->name('update');
            Route::delete('/{planta}', [PlantaController::class, 'destroy'])->name('destroy');
        });

    // Grupo de Levantamientos
    Route::name('ingenierias.levantamientos.')
        ->prefix('ingenierias/plantas/{planta}/levantamientos')
        ->scopeBindings()
        ->middleware('permiso:' . Accion::READ)
        ->group(function () {
            Route::get('/', [LevantamientoController::class, 'index'])->name('index');
            Route::get('/{levantamiento}', [LevantamientoController::class, 'show'])->name('show');
            Route::post('/', [LevantamientoController::class, 'store'])->name('store');
            Route::put('/{levantamiento}', [LevantamientoController::class, 'update'])->name('update');
            Route::delete('/{levantamiento}', [LevantamientoController::class, 'destroy'])->name('destroy');
        });
});
