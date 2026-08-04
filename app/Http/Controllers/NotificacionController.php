<?php

namespace App\Http\Controllers;

use App\Actions\Notificaciones\NotificacionesAction;
use App\Models\Notificacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function marcarLeida(Notificacion $notificacion, NotificacionesAction $action): RedirectResponse
    {
        abort_unless($notificacion->usuario_id === auth()->id(), 403);

        $action->marcarLeida($notificacion);

        return back();
    }

    public function marcarTodasLeidas(Request $request, NotificacionesAction $action): RedirectResponse
    {
        $action->marcarTodasLeidas($request->user());

        return back();
    }
}
