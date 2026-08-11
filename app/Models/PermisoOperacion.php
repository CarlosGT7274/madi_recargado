<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PermisoOperacion extends Model
{
    protected $table = 'permiso_operaciones';

    public $timestamps = false;

    protected $fillable = ['permiso_id', 'operacion_id'];

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(Permiso::class, 'permiso_id');
    }

    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class, 'operacion_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_permisos_operaciones', 'permiso_operacion_id', 'rol_id');
    }
}
