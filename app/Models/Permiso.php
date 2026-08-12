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
     * Operaciones de NEGOCIO (no básicas) declaradas explícitamente para
     * este objeto vía permiso_operaciones. Las básicas (Ver/Crear/Editar/
     * Eliminar) no viven aquí — aplican a todo objeto por default, ver
     * construirArbol().
     */
    public function operaciones(): BelongsToMany
    {
        return $this->belongsToMany(Operacion::class, 'permiso_operaciones', 'permiso_id', 'operacion_id');
    }

    public static function componerEndpoint(?string $prefijo, ?string $segmento): ?string
    {
        $partes = array_filter([$prefijo, $segmento], fn (?string $v): bool => filled($v));

        return $partes === [] ? null : implode('.', $partes);
    }

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

    /**
     * Árbol completo para la UI de administración de roles. Cada nodo
     * trae `operacionesAplicables`: la máscara de bits de las operaciones
     * que tienen sentido para ESE objeto. En una categoría (con hijos),
     * la máscara es la unión de sus propias operaciones con las de todo
     * su subárbol — así un módulo padre puede actuar como "toggle masivo"
     * de cualquier operación que aplique a alguno de sus hijos, sin
     * fingir que la categoría en sí misma tiene esa operación.
     */
    public static function arbol(): Collection
    {
        $basicas = Operacion::where('basica', true)->where('activo', true)->sum('bit');

        return self::construirArbol((int) $basicas, null)['nodos'];
    }

    /**
     * @return array{nodos: Collection, aplicablesUnion: int}
     */
    protected static function construirArbol(int $basicas, ?int $padreId, ?string $prefijoPadre = null): array
    {
        $nodos = self::with('operaciones')
            ->where('activo', true)
            ->where('padre_id', $padreId)
            ->orderBy('nombre')
            ->get()
            ->map(function (self $p) use ($basicas, $prefijoPadre) {
                $endpointCompleto = self::componerEndpoint($prefijoPadre, $p->endpoint);
                $subarbol = self::construirArbol($basicas, $p->id, $endpointCompleto);

                // Básicas aplican a todo objeto por default; operaciones de
                // negocio (aprobar, supervisar, ...) solo si están declaradas
                // explícitamente en permiso_operaciones para este permiso.
                $propias = $basicas | (int) $p->operaciones->sum('bit');
                $aplicables = $propias | $subarbol['aplicablesUnion'];

                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'endpoint' => $endpointCompleto,
                    'operacionesAplicables' => $aplicables,
                    'hijos' => $subarbol['nodos'],
                ];
            });

        return [
            'nodos' => $nodos,
            'aplicablesUnion' => $nodos->reduce(fn (int $acc, array $n) => $acc | $n['operacionesAplicables'], 0),
        ];
    }
}
