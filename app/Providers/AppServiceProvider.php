<?php

namespace App\Providers;

use App\Models\Levantamiento;
use App\Models\User;
use App\Support\Accion;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();

        Relation::morphMap([
            'levantamiento' => Levantamiento::class,
            // agrega aquí el alias de cada módulo nuevo que use imágenes
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Gates de autorización. `permiso` resuelve por nombre de recurso;
     * `controlTotal` resuelve por endpoint de ruta y exige el bitmask
     * completo (15) — pensado para acciones administrativas de un módulo
     * (aprobar, desbloquear, etc.) sin necesitar un rol "Administrador"
     * explícito. Ambos delegan en HasBitmaskAuthorization; no duplican
     * lógica de resolución de permisos.
     *
     * TODO: si el negocio necesita distinguir "editar" de "aprobar" más
     * adelante, aquí es donde se introduce ese bit nuevo — por ahora
     * controlTotal es suficiente y no amerita ABAC.
     */
    protected function configureAuthorization(): void
    {
        Gate::define('permiso', function (User $user, string $permisoNombre, int $accion): bool {
            return $user->puede($permisoNombre, $accion);
        });

        Gate::define('controlTotal', function (User $user, string $endpoint): bool {
            return $user->puedePorEndpoint($endpoint, Accion::ALL);
        });
    }
}
