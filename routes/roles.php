<?php

use App\Http\Controllers\RoleController;
use App\Support\Accion;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('permiso:'.Accion::READ)
        ->name('roles.index')
        ->get('roles', [RoleController::class, 'index']);

    Route::middleware('permiso:'.Accion::READ)
        ->get('roles/{role}', [RoleController::class, 'show'])
        ->name('roles.show');

    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::put('roles/{role}/permisos', [RoleController::class, 'permisos'])->name('roles.permisos.update');
});
