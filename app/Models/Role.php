<?php

namespace App\Models;

use App\Support\Operacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Grants directos del rol: [permiso_id => ['leer', 'aprobar', ...]].
     * No incluye herencia por jerarquía; eso lo resuelve
     * operacionesEfectivasPara().
     */
    protected ?Collection $grantsMapa = null;

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'rol_id', 'user_id')
            ->withTimestamps();
    }

    protected function grantsMapa(): Collection
    {
        return $this->grantsMapa ??= DB::table('roles_permisos')
            ->join('operaciones', 'operaciones.id', '=', 'roles_permisos.operacion_id')
            ->where('roles_permisos.rol_id', $this->id)
            ->get(['roles_permisos.permiso_id', 'operaciones.clave'])
            ->groupBy('permiso_id')
            ->map(fn (Collection $filas): array => $filas->pluck('clave')->all());
    }

    /**
     * Operaciones efectivas del rol sobre un objeto: la UNIÓN de sus
     * grants sobre el objeto y sobre todos sus ancestros. Un permiso
     * otorgado en `Ingenierías` cubre a `Plantas` (herencia descendente),
     * fiel a cómo el árbol de objetos representa módulos y submódulos.
     *
     * @return array<int, string>
     */
    public function operacionesEfectivasPara(Permiso $objeto): array
    {
        $mapa = $this->grantsMapa();
        $claves = [];
        $actual = $objeto;

        while ($actual !== null) {
            if ($mapa->has($actual->id)) {
                $claves = array_merge($claves, $mapa->get($actual->id));
            }

            $actual = $actual->padre;
        }

        return array_values(array_unique($claves));
    }

    public function tienePermiso(Permiso $objeto, string $operacion): bool
    {
        return in_array($operacion, $this->operacionesEfectivasPara($objeto), true);
    }

    /**
     * Grants directos por objeto, para pintar la matriz de un rol.
     *
     * @return array<int, array<int, string>>
     */
    public function mapaPermisosAsignados(): array
    {
        return $this->grantsMapa()->all();
    }

    /**
     * Otorga una operación concreta sobre un objeto. La validez
     * (objeto_operacion) se valida en la capa de asignación/UI; aquí se
     * confía en el llamador para permitir sembrar y probar con soltura.
     */
    public function otorgar(Permiso $objeto, string $operacion): void
    {
        $operacionId = Operacion::query()->where('clave', $operacion)->value('id');

        if ($operacionId === null) {
            return;
        }

        DB::table('roles_permisos')->updateOrInsert([
            'rol_id' => $this->id,
            'permiso_id' => $objeto->id,
            'operacion_id' => $operacionId,
        ]);

        $this->grantsMapa = null;
    }

    /**
     * Otorga TODAS las operaciones del catálogo sobre un objeto. Útil para
     * roles con control total y para sembrar/probar.
     */
    public function otorgarTodas(Permiso $objeto): void
    {
        Operacion::query()->pluck('clave')->each(fn (string $clave) => $this->otorgar($objeto, $clave));
    }

    /**
     * Reemplaza los grants del rol sobre un objeto por el conjunto dado.
     *
     * @param  array<int, string>  $operaciones
     */
    public function sincronizarObjeto(Permiso $objeto, array $operaciones): void
    {
        $ids = Operacion::query()->whereIn('clave', $operaciones)->pluck('id');

        DB::table('roles_permisos')
            ->where('rol_id', $this->id)
            ->where('permiso_id', $objeto->id)
            ->delete();

        $filas = $ids->map(fn (int $operacionId): array => [
            'rol_id' => $this->id,
            'permiso_id' => $objeto->id,
            'operacion_id' => $operacionId,
        ])->all();

        if ($filas !== []) {
            DB::table('roles_permisos')->insert($filas);
        }

        $this->grantsMapa = null;
    }

    public function revocar(Permiso $objeto): void
    {
        DB::table('roles_permisos')
            ->where('rol_id', $this->id)
            ->where('permiso_id', $objeto->id)
            ->delete();

        $this->grantsMapa = null;
    }
}
