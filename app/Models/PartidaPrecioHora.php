<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartidaPrecioHora extends Model
{
    protected $table = 'partidas_precios_hora';
    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = null;

    protected $fillable = [
        'partida_id',
        'empleado_id',
        'precio_hora',
        'creado_por_id'
    ];

    protected $casts = [
        'precio_hora' => 'decimal:2'
    ];

    public function partida()
    {
        return $this->belongsTo(Partida::class, 'partida_id');
    }
    public function empleado()
    {
        return $this->belongsTo(User::class, 'empleado_id');
    }
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }
}
