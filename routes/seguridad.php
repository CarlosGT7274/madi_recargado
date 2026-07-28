<?php

use App\Http\Controllers\Seguridad\RoleController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::name('seguridad.roles.')->prefix('seguridad/roles')->group(function () {
        Route::middleware('permiso:'.Accion::READ)
            ->get('/', [RoleController::class, 'index'])
            ->name('index');

        Route::middleware('permiso:'.Accion::READ)
            ->get('/{role}', [RoleController::class, 'show'])
            ->name('show');

        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        Route::put('/{role}/permisos', [RoleController::class, 'permisos'])->name('permisos.update');
    });
});
