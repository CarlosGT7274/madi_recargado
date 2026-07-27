<?php

use App\Http\Controllers\Seguridad\RoleController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('permiso:'.Accion::READ)
        ->name('roles.index')
        ->get('seguridad/roles', [RoleController::class, 'index']);

    Route::middleware('permiso:'.Accion::READ)
        ->get('seguridad/roles/{role}', [RoleController::class, 'show'])
        ->name('roles.show');

    Route::post('seguridad/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('seguridad/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('seguridad/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::put('seguridad/roles/{role}/permisos', [RoleController::class, 'permisos'])->name('roles.permisos.update');
});
