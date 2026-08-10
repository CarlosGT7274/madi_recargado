<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Operación del sistema en el modelo Core RBAC: la acción que un permiso
 * autoriza sobre un objeto (`leer`, `aprobar`, `firmar`, ...).
 */
class Operacion extends Model
{
    protected $table = 'operaciones';

    public $timestamps = false;

    protected $fillable = [
        'clave',
        'nombre',
        'orden',
    ];

    /**
     * Objetos protegibles para los que esta operación está declarada como
     * válida.
     */
    public function objetos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'objeto_operacion', 'operacion_id', 'permiso_id');
    }
}
