<?php

namespace App\Actions\Notificaciones;

use App\Events\NotificacionCreada;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificacionesAction
{
    /** @param array<string, mixed> $data */
    public function crearParaUsuario(User $usuario, array $data): Notificacion
    {
        $notificacion = Notificacion::create([
            ...$data,
            'usuario_id' => $usuario->id,
            'es_general' => false,
            'fecha' => $data['fecha'] ?? now(),
        ]);

        broadcast(new NotificacionCreada($notificacion));

        return $notificacion;
    }

    /**
     * @param  Collection<int, User>  $usuarios
     * @param  array<string, mixed>  $data
     * @return Collection<int, Notificacion>
     */
    public function crearParaUsuarios(Collection $usuarios, array $data): Collection
    {
        return $usuarios->map(fn (User $usuario) => $this->crearParaUsuario($usuario, $data));
    }

    public function noLeidasDe(User $usuario, int $limite = 20): Collection
    {
        return Notificacion::where('usuario_id', $usuario->id)
            ->where('leida', false)
            ->latest('fecha')
            ->limit($limite)
            ->get();
    }

    public function marcarLeida(Notificacion $notificacion): Notificacion
    {
        $notificacion->update(['leida' => true]);

        return $notificacion;
    }

    public function marcarTodasLeidas(User $usuario): void
    {
        Notificacion::where('usuario_id', $usuario->id)
            ->where('leida', false)
            ->update(['leida' => true]);
    }
}
