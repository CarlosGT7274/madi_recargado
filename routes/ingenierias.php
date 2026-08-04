<?php

use App\Http\Controllers\Ingenierias\CompraOrdenController;
use App\Http\Controllers\Ingenierias\CotizacionController;
use App\Http\Controllers\Ingenierias\InsumoController;
use App\Http\Controllers\Ingenierias\LevantamientoController;
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
            Route::get('/plantilla', [CotizacionController::class, 'plantilla'])
                ->middleware('permiso:'.Accion::CREATE)->name('plantilla');
            Route::get('/obra/{obra}', [CotizacionController::class, 'versiones'])
                ->middleware('permiso:'.Accion::READ)->name('versiones')
                ->where('obra', '.*');
            Route::get('/{cotizacion}', [CotizacionController::class, 'show'])
                ->middleware('permiso:'.Accion::READ)->name('show');
            Route::post('/', [CotizacionController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('store');
            Route::put('/{cotizacion}', [CotizacionController::class, 'update'])
                ->middleware('permiso:'.Accion::UPDATE)->name('update');
            Route::delete('/{cotizacion}', [CotizacionController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
            Route::post('/{cotizacion}/partidas', [CotizacionController::class, 'storePartida'])
                ->middleware('permiso:'.Accion::UPDATE)->name('partidas.store');
            Route::put('/{cotizacion}/partidas/{partida}', [CotizacionController::class, 'updatePartida'])
                ->middleware('permiso:'.Accion::UPDATE)->name('partidas.update');
            Route::delete('/{cotizacion}/partidas/{partida}', [CotizacionController::class, 'destroyPartida'])
                ->middleware('permiso:'.Accion::UPDATE)->name('partidas.destroy');
        });

    Route::name('ingenierias.plantas.proyectos.levantamientos.cotizaciones.insumos.')
        ->prefix('ingenierias/plantas/{planta}/proyectos/{proyecto}/levantamientos/{levantamiento}/cotizaciones/{cotizacion}/insumos')
        ->scopeBindings()
        ->group(function () {
            Route::get('/', [InsumoController::class, 'index'])
                ->middleware('permiso:'.Accion::READ)->name('index');
            Route::get('/plantilla', [InsumoController::class, 'plantilla'])
                ->middleware('permiso:'.Accion::CREATE)->name('plantilla');
            Route::post('/importar', [InsumoController::class, 'importar'])
                ->middleware('permiso:'.Accion::CREATE)->name('importar');
            Route::delete('/{insumo}', [InsumoController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
        });

    Route::name('ingenierias.plantas.proyectos.levantamientos.cotizaciones.orden-compra.')
        ->prefix('ingenierias/plantas/{planta}/proyectos/{proyecto}/levantamientos/{levantamiento}/cotizaciones/{cotizacion}/orden-compra')
        ->scopeBindings()
        ->group(function () {
            Route::post('/', [CompraOrdenController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('store');
        });
});
