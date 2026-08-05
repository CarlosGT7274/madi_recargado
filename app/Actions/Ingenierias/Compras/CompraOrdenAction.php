<?php

namespace App\Actions\Ingenierias\Compras;

use App\Models\Archivo;
use App\Models\CompraOrden;
use App\Models\Cotizacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CompraOrdenAction
{
    public function subirPdf(Cotizacion $cotizacion, UploadedFile $archivo): CompraOrden
    {
        if (! $cotizacion->tieneInsumos()) {
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
}
