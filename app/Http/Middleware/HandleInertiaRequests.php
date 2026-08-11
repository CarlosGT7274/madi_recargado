<?php

namespace App\Http\Middleware;

use App\Actions\Notificaciones\NotificacionesAction;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'permisos' => fn () => $request->user()?->permisosEfectivos() ?? [],
            'menu' => fn () => $request->user()?->menuVisible() ?? collect(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'notificaciones' => fn () => $request->user()
                ? app(NotificacionesAction::class)->noLeidasDe($request->user())
                : [],
        ];
    }
}
