<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planta extends Model
{
    use HasFactory;

    protected $table = 'plantas';

    const CREATED_AT = 'fecha_creacion';

    const UPDATED_AT = 'fecha_modificacion';

    protected $fillable = [
        'folio',
        'nombre',
        'direccion',
        'descripcion',
        'activa',
        'usuario_id',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function levantamientos(): HasMany
    {
        return $this->hasMany(Levantamiento::class, 'planta_id');
    }
}
