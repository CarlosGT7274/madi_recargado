<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un compromiso de entrega puntual de un usuario. planta_id/proyecto_id
 * son ambos nullable y definen el alcance:
 *  - null + null   -> compromiso general del usuario (aplica a cualquier
 *                     Planeación suya que no tenga uno más específico).
 *  - planta_id     -> aplica a cualquier Planeación de esa planta.
 *  - planta_id +
 *    proyecto_id   -> aplica solo a ese proyecto puntual.
 *
 * Quién gana para una Planeación concreta lo resuelve
 * User::compromisoVigentePara().
 */
class CompromisoEntrega extends Model
{
    use HasFactory;

    protected $table = 'compromisos_entrega';

    protected $fillable = [
        'user_id',
        'planta_id',
        'proyecto_id',
        'fecha',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function planta(): BelongsTo
    {
        return $this->belongsTo(Planta::class);
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function vencido(): bool
    {
        return now()->greaterThan($this->fecha);
    }

    /** 0 = más específico (proyecto), 2 = más general. Usado para desempatar cuál aplica. */
    public function especificidad(): int
    {
        return match (true) {
            $this->proyecto_id !== null => 0,
            $this->planta_id !== null => 1,
            default => 2,
        };
    }
}
