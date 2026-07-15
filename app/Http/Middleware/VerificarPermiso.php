<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso en rutas (solo la acción; el recurso se detecta solo):
 *
 *   Route::middleware('permiso:' . Accion::READ)->name('usuarios.index')->get(...);
 *   Route::middleware('permiso:' . Accion::CREATE)->name('usuarios.store')->post(...);
 *
 * El endpoint se toma del nombre de la ruta actual (`$request->route()->getName()`)
 * y se busca en `permisos.endpoint` — dinámico, en base de datos, nada
 * hardcodeado aquí. Si la ruta no tiene nombre, o ningún permiso declara
 * ese endpoint, se deniega por defecto.
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $accion): Response
    {
        $endpoint = $request->route()?->getName();

        abort_unless(
            $endpoint !== null && $request->user()?->puedePorEndpoint($endpoint, (int) $accion) === true,
            403,
        );

        return $next($request);
    }
}
