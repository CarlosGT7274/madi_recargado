<?php

use App\Http\Controllers\Seguridad\RoleController;
use App\Http\Controllers\Seguridad\UsuarioController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::name('seguridad.roles.')
        ->prefix('seguridad/roles')
        ->group(function () {
            Route::get('/', [RoleController::class, 'index'])
                ->middleware('permiso:'.Accion::READ)->name('index');
            Route::get('/{role}', [RoleController::class, 'show'])
                ->middleware('permiso:'.Accion::READ)->name('show');
            Route::post('/', [RoleController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('store');
            Route::put('/{role}', [RoleController::class, 'update'])
                ->middleware('permiso:'.Accion::UPDATE)->name('update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
            Route::put('/{role}/permisos', [RoleController::class, 'permisos'])
                ->middleware('permiso:'.Accion::UPDATE)->name('permisos');
        });

    Route::name('seguridad.usuarios.')
        ->prefix('seguridad/usuarios')
        ->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])
                ->middleware('permiso:'.Accion::READ)->name('index');
            Route::post('/', [UsuarioController::class, 'store'])
                ->middleware('permiso:'.Accion::CREATE)->name('store');
            Route::put('/{usuario}', [UsuarioController::class, 'update'])
                ->middleware('permiso:'.Accion::UPDATE)->name('update');
            Route::delete('/{usuario}', [UsuarioController::class, 'destroy'])
                ->middleware('permiso:'.Accion::DELETE)->name('destroy');
        });
});
