<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operacion extends Model
{
    protected $table = 'operaciones';

    public $timestamps = false;

    protected $fillable = ['clave', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function permisoOperaciones(): HasMany
    {
        return $this->hasMany(PermisoOperacion::class, 'operacion_id');
    }
}
