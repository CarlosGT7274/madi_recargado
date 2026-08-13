<?php

namespace App\Models;

use App\Models\Concerns\HasArchivos;
use App\Support\NumeroALetras;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizacion extends Model
{
    use HasArchivos;

    protected $table = 'cotizaciones';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_modificacion';

    /** Fija en código, igual que la dirección de MADI: no se captura ni se parsea por cotización. */
    public const MONEDA_FIJA = 'PESOS MXN';

    public const IVA_PORCENTAJE = 0.16;

    protected $fillable = [
        'levantamiento_id',
        'proyecto_id',
        'folio',
        'fecha',
        'cliente',
        'direccion',
        'obra',
        'vendedor',
        'proveedor',
        'subtotal',
        'iva',
        'total',
        'costo_hora_total',
        'tiempo_entrega',
        'dias_credito',
        'vigencia_cotizacion',
        'notas',
        'estado',
        'estatus_compra',
        'aprobador_compra_id',
        'fecha_aprobacion_compra',
        'fecha_aprobacion',
        'tiene_partidas',
        'presupuesto_consumido',
        'usuario_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'costo_hora_total' => 'decimal:2',
        'fecha_aprobacion' => 'date',
        'fecha_aprobacion_compra' => 'datetime',
        'tiene_partidas' => 'boolean',
        'presupuesto_consumido' => 'decimal:2',
    ];

    public function levantamiento()
    {
        return $this->belongsTo(Levantamiento::class, 'levantamiento_id');
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function aprobadorCompra()
    {
        return $this->belongsTo(User::class, 'aprobador_compra_id');
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(Partida::class, 'cotizacion_id');
    }

    public function insumos(): HasMany
    {
        return $this->hasMany(Insumo::class, 'cotizacion_id');
    }

    public function tieneInsumos(): bool
    {
        return $this->insumos()->exists();
    }

    public function esDeProyectoDirecto(): bool
    {
        return $this->levantamiento_id === null;
    }

    /**
     * ÚNICA fuente de verdad de "completada/aprobada" en todo el sistema.
     */
    public function estaCompletada(): bool
    {
        if ($this->esDeProyectoDirecto()) {
            return $this->tieneAutorizacion();
        }

        return $this->tieneInsumos() && $this->tieneAutorizacion();
    }

    public function tieneAutorizacion(): bool
    {
        return $this->archivos()->where('tipo_archivo', 'pdf')->exists()
            || $this->estatus_compra === 'aprobado';
    }

    /** IVA fijo al 16%, calculado — nunca capturado a mano ni leído de una columna. */
    public function ivaCalculado(): float
    {
        return round((float) $this->subtotal * self::IVA_PORCENTAJE, 2);
    }

    public function totalConIva(): float
    {
        return (float) $this->subtotal + $this->ivaCalculado();
    }

    /** Derivado del total, nunca capturado a mano (ni en Excel ni en el formulario manual). */
    public function importeLetra(): string
    {
        return NumeroALetras::convertir($this->totalConIva(), self::MONEDA_FIJA);
    }
}
