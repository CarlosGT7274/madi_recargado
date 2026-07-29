<?php

use App\Http\Controllers\Ingenierias\LevantamientoController;
use App\Http\Controllers\Ingenierias\PlantaController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Grupo de Plantas.
    // La autorización es la única fuente de verdad: cada ruta declara la
    // acción que exige (READ/CREATE/UPDATE/DELETE) y el middleware `permiso`
    // la resuelve contra el árbol de permisos de la base de datos a partir
    // del nombre de la ruta. Nada de nombres hardcodeados.
    Route::name('ingenierias.plantas.')
        ->prefix('ingenierias/plantas')
        ->group(function () {
            Route::get('/', [PlantaController::class, 'index'])
                ->middleware('permiso:'.Accion::READ)->name('index');
            Route::get('/{planta}', [PlantaController::class, 'show'])
                ->middleware('permiso:'.Accion::READ)->name('show');
            Route::post('/', [PlantaController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('store');
            Route::put('/{planta}', [PlantaController::class, 'update'])
                ->middleware('permiso:'.Accion::UPDATE)->name('update');
            Route::delete('/{planta}', [PlantaController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
        });

    // Grupo de Levantamientos.
    // Cuelgan de una planta, así que sus nombres de ruta viven bajo
    // `ingenierias.plantas.levantamientos.*`. Al resolver el permiso se toma
    // el prefijo más largo existente en la BD (`ingenierias.plantas`), de modo
    // que los levantamientos heredan los permisos de Plantas sin necesitar un
    // nodo propio ni ningún registro adicional.
    Route::name('ingenierias.plantas.levantamientos.')
        ->prefix('ingenierias/plantas/{planta}/levantamientos')
        ->scopeBindings()
        ->group(function () {
            Route::get('/data', [LevantamientoController::class, 'data'])
                ->middleware('permiso:'.Accion::READ)->name('data');
            Route::get('/', [LevantamientoController::class, 'index'])
                ->middleware('permiso:'.Accion::READ)->name('index');
            Route::get('/{levantamiento}', [LevantamientoController::class, 'show'])
                ->middleware('permiso:'.Accion::READ)->name('show');
            Route::post('/', [LevantamientoController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('store');
            Route::put('/{levantamiento}', [LevantamientoController::class, 'update'])
                ->middleware('permiso:'.Accion::UPDATE)->name('update');
            Route::delete('/{levantamiento}', [LevantamientoController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
        });
});
