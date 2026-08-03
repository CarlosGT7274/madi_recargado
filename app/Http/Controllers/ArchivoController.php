<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ArchivoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validado = $request->validate([
            'archivable_type' => ['required', 'string'],
            'archivable_id' => ['required', 'integer'],
            'imagenes' => ['required', 'array', 'min:1'],
            'imagenes.*' => ['image', 'max:5120'],
        ]);

        $clase = Relation::getMorphedModel($validado['archivable_type']);
        abort_if($clase === null, 404, 'Tipo de archivo adjunto no soportado.');

        $modelo = $clase::findOrFail($validado['archivable_id']);

        foreach ($request->file('imagenes', []) as $archivo) {
            $ruta = $archivo->store('archivos/'.$validado['archivable_type'], 'public');

            $modelo->archivos()->create([
                'archivable_type' => $validado['archivable_type'],
                'almacenamiento' => 'url',
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'tipo_archivo' => 'imagen',
                'tipo_mime' => $archivo->getClientMimeType(),
                'tamano_bytes' => $archivo->getSize(),
                'url' => $ruta,
                'storage_driver' => 'public',
                'usuario_id' => Auth::id(),
            ]);
        }

        return back();
    }

    public function destroy(Archivo $archivo): RedirectResponse
    {
        if ($archivo->url) {
            Storage::disk($archivo->storage_driver ?? 'public')->delete($archivo->url);
        }

        $archivo->delete();

        return back();
    }

    public function storeDocumento(Request $request): RedirectResponse
    {
        $validado = $request->validate([
            'archivable_type' => ['required', 'string'],
            'archivable_id' => ['required', 'integer'],
            'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $clase = Relation::getMorphedModel($validado['archivable_type']);
        abort_if($clase === null, 404, 'Tipo de archivo adjunto no soportado.');

        $modelo = $clase::findOrFail($validado['archivable_id']);
        $archivo = $request->file('archivo');
        $ruta = $archivo->store('archivos/'.$validado['archivable_type'], 'public');

        $modelo->archivos()->create([
            'archivable_type' => $validado['archivable_type'],
            'almacenamiento' => 'url',
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'tipo_archivo' => 'excel',
            'tipo_mime' => $archivo->getClientMimeType(),
            'tamano_bytes' => $archivo->getSize(),
            'url' => $ruta,
            'storage_driver' => 'public',
            'usuario_id' => Auth::id(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Excel guardado. Aún no ha sido procesado.']);

        return back();
    }
}
