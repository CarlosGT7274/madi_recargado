<?php

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
});
