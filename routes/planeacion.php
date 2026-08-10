<?php

use App\Http\Controllers\Ingenierias\Planeacion\AsignacionController;
use App\Http\Controllers\Ingenierias\Planeacion\IncidenciaController;
use App\Http\Controllers\Ingenierias\Planeacion\PlaneacionController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::name('planeacion.')
        ->prefix('planeacion')
        ->group(function () {
            Route::get('/', [PlaneacionController::class, 'index'])
                ->middleware('permiso:'.Accion::READ)->name('index');
        });

    Route::name('planeacion.plantas.proyectos.')
        ->prefix('planeacion/plantas/{planta}/proyectos/{proyecto}')
        ->scopeBindings()
        ->group(function () {
            Route::get('/planeaciones', [PlaneacionController::class, 'porProyecto'])
                ->middleware('permiso:'.Accion::READ)->name('planeaciones.index');
            Route::post('/planeaciones', [PlaneacionController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('planeaciones.store');
        });

    Route::name('planeacion.')
        ->prefix('planeacion/{planeacion}')
        ->group(function () {
            Route::get('/', [PlaneacionController::class, 'show'])
                ->middleware('permiso:'.Accion::READ)->name('show');
            Route::delete('/', [PlaneacionController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
            Route::post('/enviar', [PlaneacionController::class, 'enviar'])
                ->middleware('permiso:'.Accion::UPDATE)->name('enviar');
            Route::post('/aprobar', [PlaneacionController::class, 'aprobar'])
                ->middleware('permiso:'.Accion::UPDATE)->name('aprobar');
            Route::post('/rechazar', [PlaneacionController::class, 'rechazar'])
                ->middleware('permiso:'.Accion::UPDATE)->name('rechazar');
            Route::post('/reportar-nomina', [PlaneacionController::class, 'reportarNomina'])
                ->middleware('permiso:'.Accion::UPDATE)->name('reportar-nomina');

            Route::post('/asignaciones', [AsignacionController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('asignaciones.store');
            Route::put('/asignaciones/{asignacion}', [AsignacionController::class, 'update'])
                ->middleware('permiso:'.Accion::UPDATE)->name('asignaciones.update');
            Route::delete('/asignaciones/{asignacion}', [AsignacionController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('asignaciones.destroy');

            Route::post('/asignaciones/{asignacion}/incidencias', [IncidenciaController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('incidencias.store');
        });
});
