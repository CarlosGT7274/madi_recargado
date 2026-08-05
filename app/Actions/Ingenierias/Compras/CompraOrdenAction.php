<?php

namespace App\Actions\Ingenierias\Compras;

use App\Actions\Notificaciones\NotificacionesAction;
use App\Models\Archivo;
use App\Models\CompraOrden;
use App\Models\Cotizacion;
use App\Models\Permiso;
use App\Models\Role;
use App\Support\Accion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CompraOrdenAction
{
    public function __construct(
        private readonly NotificacionesAction $notificaciones,
    ) {}

    public function subirPdf(Cotizacion $cotizacion, UploadedFile $archivo): CompraOrden
    {
        if (! $cotizacion->esDeProyectoDirecto() && ! $cotizacion->tieneInsumos()) {
            throw ValidationException::withMessages([
                'archivo' => 'Debes completar la Explosión de Insumos antes de subir la Orden de Compra.',
            ]);
        }

        $ordenCompra = $cotizacion->ordenCompra
            ?? $cotizacion->ordenCompra()->create(['usuario_registro_id' => Auth::id()]);

        $ruta = $archivo->store('archivos/compra_orden', 'public');

        $ordenCompra->archivos()->create([
            'archivable_type' => 'compra_orden',
            'almacenamiento' => 'url',
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'tipo_archivo' => 'pdf',
            'tipo_mime' => $archivo->getClientMimeType(),
            'tamano_bytes' => $archivo->getSize(),
            'url' => $ruta,
            'storage_driver' => 'public',
            'usuario_id' => Auth::id(),
        ]);

        return $ordenCompra->fresh();
    }

    public function eliminarPdf(Archivo $archivo): void
    {
        if ($archivo->url) {
            Storage::disk($archivo->storage_driver ?? 'public')->delete($archivo->url);
        }

        $archivo->delete();
    }

    public function solicitarRevision(Cotizacion $cotizacion): CompraOrden
    {
        $ordenCompra = $cotizacion->ordenCompra
            ?? $cotizacion->ordenCompra()->create(['usuario_registro_id' => Auth::id()]);

        $ordenCompra->update(['estatus_compra' => 'en_cotizacion']);

        $this->notificaciones->crearParaUsuarios($this->administradores(), [
            'mensaje' => "Cotización {$cotizacion->folio} solicita aprobación sin orden de compra.",
            'destino_area' => 'compras',
            'modulo' => 'ingenierias.compras',
            'tipo_entidad' => 'compra_orden',
            'entidad_id' => $ordenCompra->id,
        ]);

        return $ordenCompra->fresh();
    }

    public function aprobar(CompraOrden $ordenCompra): CompraOrden
    {
        $ordenCompra->update([
            'estatus_compra' => 'aprobado',
            'fecha_aprobacion' => now(),
            'usuario_modificacion_id' => Auth::id(),
        ]);

        $this->notificaciones->crearParaUsuario($ordenCompra->usuarioRegistro, [
            'mensaje' => 'Tu solicitud de revisión sin orden de compra fue aprobada.',
            'destino_area' => 'compras',
            'modulo' => 'ingenierias.compras',
            'tipo_entidad' => 'compra_orden',
            'entidad_id' => $ordenCompra->id,
        ]);

        return $ordenCompra->fresh();
    }

    public function rechazar(CompraOrden $ordenCompra): CompraOrden
    {
        $ordenCompra->update([
            'estatus_compra' => 'rechazado',
            'usuario_modificacion_id' => Auth::id(),
        ]);

        $this->notificaciones->crearParaUsuario($ordenCompra->usuarioRegistro, [
            'mensaje' => 'Tu solicitud de revisión sin orden de compra fue rechazada.',
            'destino_area' => 'compras',
            'modulo' => 'ingenierias.compras',
            'tipo_entidad' => 'compra_orden',
            'entidad_id' => $ordenCompra->id,
        ]);

        return $ordenCompra->fresh();
    }

    private function administradores(): Collection
    {
        $raiz = Permiso::whereNull('padre_id')
            ->where('endpoint', 'ingenierias')
            ->first();

        if ($raiz === null) {
            return collect();
        }

        return Role::with('usuarios')
            ->get()
            ->filter(fn (Role $rol) => $rol->tienePermiso($raiz, Accion::ALL))
            ->flatMap(fn (Role $rol) => $rol->usuarios)
            ->values();
    }
}
