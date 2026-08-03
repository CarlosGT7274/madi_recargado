<?php

namespace App\Models;

use App\Models\Concerns\HasArchivos;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Levantamiento extends Model
{
    use HasArchivos;
    use HasFactory;

    protected $table = 'levantamientos';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'planta_id',
        'proyecto_id',
        'folio',
        'nombre',
        'cliente',
        'obra',
        'solicitante',
        'fecha_solicitud',
        'usuario_requiriente',
        'correo_usuario',
        'area_trabajo',
        'titulo_cotizacion',
        'trabajos_alturas_certificado',
        'trabajos_alturas_notas',
        'espacios_confinados_aplica',
        'espacios_confinados_certificado',
        'espacios_confinados_notas',
        'corte_soldadura_aplica',
        'corte_soldadura_certificado',
        'corte_soldadura_notas',
        'izaje_aplica',
        'izaje_certificado',
        'izaje_notas',
        'apertura_lineas_aplica',
        'apertura_lineas_certificado',
        'apertura_lineas_notas',
        'excavacion_aplica',
        'excavacion_certificado',
        'excavacion_notas',
        'notas_maquinaria',
        'estatus_admin',
        'medio_solicitud',
        'prioridad',
        'notas_admin',
        'fecha_levantamiento_programada',
        'fecha_envio_cotizacion_programada',
        'fecha_cotizacion_enviada',
        'usuario_id',
    ];

    protected $attributes = [
        'estatus_admin' => 'recibida',
        'prioridad' => 'normal',
    ];

    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'date',
            'trabajos_alturas_certificado' => 'boolean',
            'espacios_confinados_aplica' => 'boolean',
            'espacios_confinados_certificado' => 'boolean',
            'corte_soldadura_aplica' => 'boolean',
            'corte_soldadura_certificado' => 'boolean',
            'izaje_aplica' => 'boolean',
            'izaje_certificado' => 'boolean',
            'apertura_lineas_aplica' => 'boolean',
            'apertura_lineas_certificado' => 'boolean',
            'excavacion_aplica' => 'boolean',
            'excavacion_certificado' => 'boolean',
            'fecha_levantamiento_programada' => 'date',
            'fecha_envio_cotizacion_programada' => 'date',
            'fecha_cotizacion_enviada' => 'date',
        ];
    }

    public function planta(): BelongsTo
    {
        return $this->belongsTo(Planta::class, 'planta_id');
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class, 'levantamiento_id');
    }

    /**
     * Alias para el route model binding anidado (scopeBindings()).
     * Laravel pluraliza "Cotizacion" con reglas de inglés y busca
     * cotizacions() en vez de cotizaciones() — este método solo
     * reenvía a la relación real para que el binding funcione.
     */
    public function cotizacions(): HasMany
    {
        return $this->cotizaciones();
    }
}
