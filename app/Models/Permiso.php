<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Permiso extends Model
{
    protected $table = 'permisos';

    public $timestamps = false;

    protected $fillable = [
        'padre_id',
        'nombre',
        'endpoint',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(self::class, 'padre_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_permisos', 'permiso_id', 'rol_id')
            ->withPivot('permisos');
    }

    /**
     * Une un prefijo heredado con un segmento propio, ignorando los vacíos.
     * Regla única de composición de endpoints en todo el sistema:
     * `seguridad` + `roles` => `seguridad.roles`.
     */
    public static function componerEndpoint(?string $prefijo, ?string $segmento): ?string
    {
        $partes = array_filter([$prefijo, $segmento], fn (?string $v): bool => filled($v));

        return $partes === [] ? null : implode('.', $partes);
    }

    /**
     * Endpoint completo derivado de la jerarquía (padre_id). Cada registro
     * solo almacena su propio segmento; el path completo se reconstruye
     * recorriendo los padres. La BD es la única fuente de verdad.
     */
    public function endpointCompleto(): ?string
    {
        $segmentos = [];
        $actual = $this;

        while ($actual !== null) {
            if (filled($actual->endpoint)) {
                array_unshift($segmentos, $actual->endpoint);
            }

            $actual = $actual->padre;
        }

        return $segmentos === [] ? null : implode('.', $segmentos);
    }

    public static function arbol(): Collection
    {
        return self::construirArbol(null);
    }

    protected static function construirArbol(?int $padreId, ?string $prefijoPadre = null): Collection
    {
        return self::where('activo', true)
            ->where('padre_id', $padreId)
            ->orderBy('nombre')
            ->get()
            ->map(function (self $p) use ($prefijoPadre) {
                $endpointCompleto = self::componerEndpoint($prefijoPadre, $p->endpoint);

                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'endpoint' => $endpointCompleto,
                    'hijos' => self::construirArbol($p->id, $endpointCompleto),
                ];
            });
    }
}
