<?php

use App\Http\Controllers\Ingenierias\CotizacionController;
use App\Http\Controllers\Ingenierias\LevantamientoController;
use App\Http\Controllers\Ingenierias\PlantaController;
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

    Route::name('ingenierias.plantas.levantamientos.cotizaciones.')
        ->prefix('ingenierias/plantas/{planta}/levantamientos/{levantamiento}/cotizaciones')
        ->scopeBindings()
        ->group(function () {
            Route::get('/{cotizacion}', [CotizacionController::class, 'show'])
                ->middleware('permiso:'.Accion::READ)->name('show');
            Route::post('/', [CotizacionController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('store');
            Route::put('/{cotizacion}', [CotizacionController::class, 'update'])
                ->middleware('permiso:'.Accion::UPDATE)->name('update');
            Route::delete('/{cotizacion}', [CotizacionController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
        });
});
