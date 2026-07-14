<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    /**
     * Reads the current route automatically (its name, falling back to the
     * URI path) and matches it against `permisos.endpoint`. Only the CRUD
     * action needs to be passed explicitly:
     *
     *   Route::get('/compras/ordenes', ...)
     *       ->name('compras.ordenes.index')
     *       ->middleware('permiso:' . Accion::READ);
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $accion): Response
    {
        $endpoint = $request->route()?->getName() ?? $request->path();

        abort_unless(
            $request->user()?->tienePermiso($endpoint, (int) $accion) === true,
            403,
        );

        return $next($request);
    }
}
