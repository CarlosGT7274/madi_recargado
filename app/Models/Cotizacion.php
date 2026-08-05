<?php

namespace App\Models;

use App\Models\Concerns\HasArchivos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cotizacion extends Model
{
    use HasArchivos;

    protected $table = 'cotizaciones';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'levantamiento_id',
        'folio',
        'fecha',
        'para',
        'cliente',
        'direccion',
        'obra',
        'vendedor',
        'proveedor',
        'correo_vendedor',
        'subtotal',
        'iva',
        'total',
        'costo_hora_total',
        'importe_letra',
        'moneda',
        'tiempo_entrega',
        'dias_credito',
        'vigencia_cotizacion',
        'notas',
        'estado',
        'fecha_aprobacion',
        'tiene_insumos',
        'tiene_orden_compra',
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
        'tiene_insumos' => 'boolean',
        'tiene_orden_compra' => 'boolean',
        'tiene_partidas' => 'boolean',
        'presupuesto_consumido' => 'decimal:2',
    ];

    public function levantamiento()
    {
        return $this->belongsTo(Levantamiento::class, 'levantamiento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(Partida::class, 'cotizacion_id');
    }

    public function insumos(): HasMany
    {
        return $this->hasMany(Insumo::class, 'cotizacion_id');
    }

    public function ordenCompra(): HasOne
    {
        return $this->hasOne(CompraOrden::class, 'cotizacion_id');
    }

    public function tieneInsumos(): bool
    {
        return $this->insumos()->exists();
    }

    public function tieneOrdenAprobada(): bool
    {
        return $this->ordenCompra?->archivos()->where('tipo_archivo', 'pdf')->exists() ?? false;
    }

    public function estaCompletada(): bool
    {
        return $this->tieneInsumos() && $this->tieneOrdenAprobada();
    }

    public function estaAprobada(): bool
    {
        return $this->estado === 'aprobada';
    }
}
