<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso en rutas (solo la operación; el objeto se detecta solo):
 *
 *   Route::middleware('permiso:' . Operacion::LEER)->name('usuarios.index')->get(...);
 *   Route::middleware('permiso:' . Operacion::CREAR)->name('usuarios.store')->post(...);
 *   Route::middleware('permiso:' . Operacion::APROBAR)->name('planeacion.aprobar')->post(...);
 *
 * El endpoint se toma del nombre de la ruta actual (`$request->route()->getName()`)
 * y se resuelve contra el árbol de objetos (`permisos.endpoint`) — dinámico,
 * en base de datos, nada hardcodeado aquí. La operación es una clave del
 * catálogo (`operaciones.clave`). Si la ruta no tiene nombre, o ningún
 * objeto declara ese endpoint, se deniega por defecto.
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $operacion): Response
    {
        $endpoint = $request->route()?->getName();

        abort_unless(
            $endpoint !== null && $request->user()?->puedePorEndpoint($endpoint, $operacion) === true,
            403,
        );

        return $next($request);
    }
}
