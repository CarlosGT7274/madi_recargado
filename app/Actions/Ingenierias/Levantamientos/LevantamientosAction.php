<?php

namespace App\Actions\Ingenierias\Levantamientos;

use App\Models\Archivo;
use App\Models\Levantamiento;
use App\Models\Proyecto;
use App\Services\FolioService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class LevantamientosAction
{
    public function __construct(
        private readonly FolioService $folios,
    ) {}

    public function list(Proyecto $proyecto): Collection
    {
        return $proyecto->levantamientos()
            ->latest('id')
            ->get()
            ->map(fn (Levantamiento $l) => [
                'id' => $l->id,
                'folio' => $l->folio,
                'nombre' => $l->nombre,
                'cliente' => $l->cliente,
                'prioridad' => $l->prioridad,
                'estatus_admin' => $l->estatus_admin,
                'creado' => $l->fecha_creacion
                    ? Carbon::parse($l->fecha_creacion)->format('d/m/Y')
                    : null,
                'creado_iso' => $l->fecha_creacion
                    ? Carbon::parse($l->fecha_creacion)->format('Y-m-d')
                    : null,
            ]);
    }

    public function detail(Levantamiento $levantamiento): array
    {
        return [
            'id' => $levantamiento->id,
            'planta_id' => $levantamiento->planta_id,
            'folio' => $levantamiento->folio,
            'nombre' => $levantamiento->nombre,
            'cliente' => $levantamiento->cliente,
            'obra' => $levantamiento->obra,
            'solicitante' => $levantamiento->solicitante,
            'fecha_solicitud' => $levantamiento->fecha_solicitud?->format('Y-m-d'),
            'usuario_requiriente' => $levantamiento->usuario_requiriente,
            'correo_usuario' => $levantamiento->correo_usuario,
            'area_trabajo' => $levantamiento->area_trabajo,
            'titulo_cotizacion' => $levantamiento->titulo_cotizacion,
            'medio_solicitud' => $levantamiento->medio_solicitud,
            'prioridad' => $levantamiento->prioridad,
            'estatus_admin' => $levantamiento->estatus_admin,
            'trabajos_alturas_certificado' => $levantamiento->trabajos_alturas_certificado,
            'trabajos_alturas_notas' => $levantamiento->trabajos_alturas_notas,
            'espacios_confinados_aplica' => $levantamiento->espacios_confinados_aplica,
            'espacios_confinados_certificado' => $levantamiento->espacios_confinados_certificado,
            'espacios_confinados_notas' => $levantamiento->espacios_confinados_notas,
            'corte_soldadura_aplica' => $levantamiento->corte_soldadura_aplica,
            'corte_soldadura_certificado' => $levantamiento->corte_soldadura_certificado,
            'corte_soldadura_notas' => $levantamiento->corte_soldadura_notas,
            'izaje_aplica' => $levantamiento->izaje_aplica,
            'izaje_certificado' => $levantamiento->izaje_certificado,
            'izaje_notas' => $levantamiento->izaje_notas,
            'apertura_lineas_aplica' => $levantamiento->apertura_lineas_aplica,
            'apertura_lineas_certificado' => $levantamiento->apertura_lineas_certificado,
            'apertura_lineas_notas' => $levantamiento->apertura_lineas_notas,
            'excavacion_aplica' => $levantamiento->excavacion_aplica,
            'excavacion_certificado' => $levantamiento->excavacion_certificado,
            'excavacion_notas' => $levantamiento->excavacion_notas,
            'notas_maquinaria' => $levantamiento->notas_maquinaria,
            'notas_admin' => $levantamiento->notas_admin,
            'fecha_levantamiento_programada' => $levantamiento->fecha_levantamiento_programada?->format('Y-m-d'),
            'fecha_envio_cotizacion_programada' => $levantamiento->fecha_envio_cotizacion_programada?->format('Y-m-d'),
            'fecha_cotizacion_enviada' => $levantamiento->fecha_cotizacion_enviada?->format('Y-m-d'),
            'creado' => $levantamiento->fecha_creacion
                ? Carbon::parse($levantamiento->fecha_creacion)->format('d/m/Y H:i')
                : null,
            'modificado' => $levantamiento->fecha_modificacion
                ? Carbon::parse($levantamiento->fecha_modificacion)->format('d/m/Y H:i')
                : null,
            'imagenes' => $levantamiento->imagenes->map(fn (Archivo $a) => [
                'id' => $a->id,
                'url' => $a->urlPublica(),
                'nombreArchivo' => $a->nombre_archivo,
            ]),
        ];
    }

    public function create(Proyecto $proyecto, array $data): Levantamiento
    {
        $data['folio'] = $this->folios->siguiente('ingenierias', 'levantamiento', 'LEV');
        $data['solicitante'] = Auth::user()?->name;
        $data['fecha_solicitud'] = now()->toDateString();
        unset($data['fecha_cotizacion_enviada']); // solo se llena al aprobar cotización

        return $proyecto->levantamientos()->create([
            ...$data,
            'planta_id' => $proyecto->planta_id,
            'usuario_id' => Auth::id(),
        ]);
    }

    public function update(Levantamiento $levantamiento, array $data): Levantamiento
    {
        // Nunca editables desde el formulario, sin importar lo que llegue del cliente
        unset($data['solicitante'], $data['fecha_solicitud'], $data['fecha_cotizacion_enviada']);

        $levantamiento->update($data);

        return $levantamiento;
    }

    public function delete(Levantamiento $levantamiento): void
    {
        $levantamiento->delete();
    }
}
