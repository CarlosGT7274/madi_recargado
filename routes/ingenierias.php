<?php

use App\Http\Controllers\Ingenierias\LevantamientoController;
use App\Http\Controllers\Ingenierias\PlantaController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('permiso:'.Accion::READ)
        ->name('ingenierias.plantas.index')
        ->get('ingenierias/plantas', [PlantaController::class, 'index']);

    Route::middleware('permiso:'.Accion::READ)
        ->get('ingenierias/plantas/{planta}', [PlantaController::class, 'show'])
        ->name('ingenierias.plantas.show');

    Route::post('ingenierias/plantas', [PlantaController::class, 'store'])
        ->name('ingenierias.plantas.store');

    Route::put('ingenierias/plantas/{planta}', [PlantaController::class, 'update'])
        ->name('ingenierias.plantas.update');

    Route::delete('ingenierias/plantas/{planta}', [PlantaController::class, 'destroy'])
        ->name('ingenierias.plantas.destroy');

    /*
     * Levantamientos: anidados bajo una planta. El nombre de ruta
     * `ingenierias.plantas.levantamientos.*` resuelve al permiso
     * `Levantamientos` (segmento `levantamientos` bajo `plantas`).
     */
    Route::middleware('permiso:'.Accion::READ)
        ->get('ingenierias/plantas/{planta}/levantamientos/{levantamiento}', [LevantamientoController::class, 'show'])
        ->name('ingenierias.plantas.levantamientos.show');

    Route::post('ingenierias/plantas/{planta}/levantamientos', [LevantamientoController::class, 'store'])
        ->name('ingenierias.plantas.levantamientos.store');

    Route::put('ingenierias/plantas/{planta}/levantamientos/{levantamiento}', [LevantamientoController::class, 'update'])
        ->name('ingenierias.plantas.levantamientos.update');

    Route::delete('ingenierias/plantas/{planta}/levantamientos/{levantamiento}', [LevantamientoController::class, 'destroy'])
        ->name('ingenierias.plantas.levantamientos.destroy');
});
