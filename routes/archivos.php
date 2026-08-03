<?php

use App\Http\Controllers\ArchivoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('archivos', [ArchivoController::class, 'store'])->name('archivos.store');
    Route::delete('archivos/{archivo}', [ArchivoController::class, 'destroy'])->name('archivos.destroy');
});
