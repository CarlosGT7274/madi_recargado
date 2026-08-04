<?php

namespace App\Events;

use App\Models\Notificacion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NotificacionCreada implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public Notificacion $notificacion) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("usuarios.{$this->notificacion->usuario_id}")];
    }

    public function broadcastAs(): string
    {
        return 'notificacion.creada';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notificacion->id,
            'mensaje' => $this->notificacion->mensaje,
            'modulo' => $this->notificacion->modulo,
            'tipo_entidad' => $this->notificacion->tipo_entidad,
            'entidad_id' => $this->notificacion->entidad_id,
            'fecha' => $this->notificacion->fecha->toIso8601String(),
        ];
    }
}
