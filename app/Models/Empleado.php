<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Empleado extends Model
{
    protected $fillable = [
        'nombre',
        'puesto',
        'precio_hora_general',
        'activo',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'precio_hora_general' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Fecha/hora límite para que este empleado envíe su Planeación de la
     * semana $anio/$semana, según su corte configurado. Null si no tiene
     * corte asignado (sin restricción).
     */
    public function corteEntregaPara(int $anio, int $semana): ?Carbon
    {
        if ($this->corte_dia_semana === null || $this->corte_hora === null) {
            return null;
        }

        $diasIndice = [
            'lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'jueves' => 4,
            'viernes' => 5, 'sabado' => 6, 'domingo' => 7,
        ];

        $semanaBase = $this->corte_semana_relativa === 'anterior' ? $semana - 1 : $semana;
        $anioBase = $anio;

        if ($semanaBase < 1) {
            $anioBase--;
            $semanaBase = Carbon::create($anioBase, 12, 28)->isoWeek();
        }

        return Carbon::now()
            ->setISODate($anioBase, $semanaBase, $diasIndice[$this->corte_dia_semana])
            ->setTimeFromTimeString($this->corte_hora->format('H:i'));
    }
}
