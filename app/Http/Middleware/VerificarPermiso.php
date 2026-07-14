<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso en rutas (números, no palabras):
 *
 *   Route::middleware('permiso:usuarios,' . Accion::READ)->get(...);
 *   Route::middleware('permiso:usuarios,' . Accion::CREATE)->post(...);
 *
 * "usuarios" es el slug del recurso en la tabla `recursos` (dinámico,
 * no hardcodeado en enum). Este es el ÚNICO middleware de permisos —
 * el viejo `CheckPermiso` (que pedía 'READ'/'CREATE' como palabras) y el
 * `VerificarPermiso` anterior (que dependía de `permisos.endpoint`) quedan
 * eliminados.
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $recursoSlug, string $accion): Response
    {
        abort_unless(
            $request->user()?->puede($recursoSlug, (int) $accion) === true,
            403,
        );

        return $next($request);
    }
}
