<?php

use App\Http\Controllers\Seguridad\RoleController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::name('seguridad.roles.')
        ->prefix('seguridad/roles')
        ->middleware('permiso:'.Accion::READ)
        ->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('/{role}', [RoleController::class, 'show'])->name('show');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::put('/{role}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
            Route::put('/{role}/permisos', [RoleController::class, 'permisos'])->name('permisos.update');
        });
});
