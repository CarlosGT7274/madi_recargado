<?php

namespace App\Http\Middleware;

use App\Support\Authorization\Permiso;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso en rutas:
 *   Route::middleware('permiso:usuarios,READ')->get(...);
 *   Route::middleware('permiso:usuarios,CREATE')->post(...);
 *
 * "usuarios" es el slug del recurso en la tabla `recursos` (dinamico, no hardcodeado en enum).
 * READ/CREATE/UPDATE/DELETE se resuelven contra las constantes de Permiso.
 */
class CheckPermiso
{
    public function handle(Request $request, Closure $next, string $recursoSlug, string $accion): Response
    {
        $accionValor = match (strtoupper($accion)) {
            'READ' => Permiso::READ,
            'CREATE' => Permiso::CREATE,
            'UPDATE' => Permiso::UPDATE,
            'DELETE' => Permiso::DELETE,
            'ALL' => Permiso::ALL,
            default => throw new \InvalidArgumentException("Acción de permiso desconocida: {$accion}"),
        };

        $user = $request->user();

        if (! $user || ! method_exists($user, 'puede') || ! $user->puede($recursoSlug, $accionValor)) {
            abort(403, 'No tienes permisos para realizar esta acción.');
        }

        return $next($request);
    }
}
