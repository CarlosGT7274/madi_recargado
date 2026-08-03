<?php

use App\Http\Controllers\Ingenierias\CotizacionController;
use App\Http\Controllers\Ingenierias\Cotizaciones\PartidaController;
use App\Http\Controllers\Ingenierias\LevantamientoController; // ← este cambia
use App\Http\Controllers\Ingenierias\PlantaController;
use App\Http\Controllers\Ingenierias\ProyectoController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
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

    Route::name('ingenierias.plantas.proyectos.')
        ->prefix('ingenierias/plantas/{planta}/proyectos')
        ->scopeBindings()
        ->group(function () {
            Route::get('/create', [ProyectoController::class, 'create'])
                ->middleware('permiso:'.Accion::CREATE)->name('create');
            Route::get('/{proyecto}', [ProyectoController::class, 'show'])
                ->middleware('permiso:'.Accion::READ)->name('show');
            Route::post('/', [ProyectoController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('store');
            Route::put('/{proyecto}', [ProyectoController::class, 'update'])
                ->middleware('permiso:'.Accion::UPDATE)->name('update');
            Route::delete('/{proyecto}', [ProyectoController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
        });

    Route::name('ingenierias.plantas.proyectos.levantamientos.')
        ->prefix('ingenierias/plantas/{planta}/proyectos/{proyecto}/levantamientos')
        ->scopeBindings()
        ->group(function () {
            Route::get('/create', [LevantamientoController::class, 'create'])
                ->middleware('permiso:'.Accion::CREATE)->name('create');
            Route::get('/plantilla', [LevantamientoController::class, 'plantilla'])
                ->middleware('permiso:'.Accion::CREATE)->name('plantilla');
            Route::post('/importar', [LevantamientoController::class, 'importar'])
                ->middleware('permiso:'.Accion::CREATE)->name('importar');
            Route::get('/data', [LevantamientoController::class, 'data'])
                ->middleware('permiso:'.Accion::READ)->name('data');
            Route::get('/{levantamiento}', [LevantamientoController::class, 'show'])
                ->middleware('permiso:'.Accion::READ)->name('show');
            Route::post('/', [LevantamientoController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('store');
            Route::put('/{levantamiento}', [LevantamientoController::class, 'update'])
                ->middleware('permiso:'.Accion::UPDATE)->name('update');
            Route::delete('/{levantamiento}', [LevantamientoController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
        });

    Route::name('ingenierias.plantas.proyectos.levantamientos.cotizaciones.')
        ->prefix('ingenierias/plantas/{planta}/proyectos/{proyecto}/levantamientos/{levantamiento}/cotizaciones')
        ->scopeBindings()
        ->group(function () {
            Route::get('/', [CotizacionController::class, 'index'])
                ->middleware('permiso:'.Accion::READ)->name('index');
            Route::get('/plantilla-partidas', [PartidaController::class, 'plantillaGenerica'])
                ->middleware('permiso:'.Accion::CREATE)->name('plantilla-partidas');
            Route::get('/{cotizacion}', [CotizacionController::class, 'show'])
                ->middleware('permiso:'.Accion::READ)->name('show');
            Route::post('/', [CotizacionController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('store');
            Route::put('/{cotizacion}', [CotizacionController::class, 'update'])
                ->middleware('permiso:'.Accion::UPDATE)->name('update');
            Route::delete('/{cotizacion}', [CotizacionController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
        });

    Route::name('ingenierias.plantas.proyectos.levantamientos.cotizaciones.partidas.')
        ->prefix('ingenierias/plantas/{planta}/proyectos/{proyecto}/levantamientos/{levantamiento}/cotizaciones/{cotizacion}/partidas')
        ->scopeBindings()
        ->group(function () {
            Route::get('/plantilla', [PartidaController::class, 'plantilla'])
                ->middleware('permiso:'.Accion::CREATE)->name('plantilla');
            Route::post('/importar', [PartidaController::class, 'importar'])
                ->middleware('permiso:'.Accion::CREATE)->name('importar');
            Route::post('/', [PartidaController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('store');
            Route::put('/{partida}', [PartidaController::class, 'update'])
                ->middleware('permiso:'.Accion::UPDATE)->name('update');
            Route::delete('/{partida}', [PartidaController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
        });
});
