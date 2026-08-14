<?php

use App\Http\Controllers\Ingenierias\Planeacion\AsignacionController;
use App\Http\Controllers\Ingenierias\Planeacion\IncidenciaController;
use App\Http\Controllers\Ingenierias\Planeacion\PlaneacionController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::name('ingenierias.planeacion.')
        ->prefix('planeacion')
        ->group(function () {
            Route::get('/', [PlaneacionController::class, 'index'])
                ->middleware('permiso:'.Accion::READ)->name('index');
            Route::get('/nueva', [PlaneacionController::class, 'create'])
                ->middleware('permiso:'.Accion::CREATE)->name('create');
        });

    Route::name('ingenierias.planeacion.plantas.proyectos.')
        ->prefix('planeacion/plantas/{planta}/proyectos/{proyecto}')
        ->scopeBindings()
        ->group(function () {
            Route::get('/planeaciones', [PlaneacionController::class, 'porProyecto'])
                ->middleware('permiso:'.Accion::READ)->name('planeaciones.index');
            Route::post('/planeaciones', [PlaneacionController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('planeaciones.store');
            Route::get('/cotizaciones-aprobadas', [PlaneacionController::class, 'cotizacionesAprobadas'])
                ->middleware('permiso:'.Accion::READ)->name('cotizaciones-aprobadas');
            Route::get('/cotizaciones/{cotizacion}/partidas', [PlaneacionController::class, 'partidasDeCotizacion'])
                ->middleware('permiso:'.Accion::READ)->name('cotizaciones.partidas');
        });

    Route::name('ingenierias.planeacion.')
        ->prefix('planeacion/{planeacion}')
        ->group(function () {
            Route::get('/', [PlaneacionController::class, 'show'])
                ->middleware('permiso:'.Accion::READ)->name('show');
            Route::delete('/', [PlaneacionController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
            Route::post('/enviar', [PlaneacionController::class, 'enviar'])
                ->middleware('permiso:'.Accion::UPDATE)->name('enviar');
            Route::post('/aprobar', [PlaneacionController::class, 'aprobar'])
                ->middleware('permiso:aprobar')->name('aprobar');
            Route::post('/rechazar', [PlaneacionController::class, 'rechazar'])
                ->middleware('permiso:aprobar')->name('rechazar');
            Route::patch('/cronograma', [PlaneacionController::class, 'actualizarCronograma'])
                ->middleware('permiso:'.Accion::UPDATE)->name('cronograma.update');
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
