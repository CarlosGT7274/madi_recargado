<?php

use App\Http\Controllers\RoleController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('permiso:'.Accion::READ)
        ->name('seguridad.roles.index')
        ->get('seguridad/roles', [RoleController::class, 'index']);

    Route::middleware('permiso:'.Accion::READ)
        ->get('seguridad/roles/{role}', [RoleController::class, 'show'])
        ->name('seguridad.roles.show');

    Route::post('seguridad/roles', [RoleController::class, 'store'])->name('seguridad.roles.store');
    Route::put('seguridad/roles/{role}', [RoleController::class, 'update'])->name('seguridad.roles.update');
    Route::delete('seguridad/roles/{role}', [RoleController::class, 'destroy'])->name('seguridad.roles.destroy');
    Route::put('seguridad/roles/{role}/permisos', [RoleController::class, 'permisos'])->name('seguridad.roles.permisos.update');
});
