<?php

// database/migrations/2026_08_14_000001_drop_enviar_rechazar_operaciones.php

use App\Models\Operacion;
use App\Models\PermisoOperacion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Enviar/Rechazar nunca fueron parte del catálogo real
     * (Ver/Crear/Editar/Eliminar/Aprobar/Supervisar). "Enviar" es una
     * acción del dueño de su propia Planeación (se valida por
     * pertenencia, no por RBAC). "Rechazar" es competencia de quien
     * puede Aprobar — reutiliza esa operación.
     */
    public function up(): void
    {
        $ids = Operacion::whereIn('clave', ['enviar', 'rechazar'])->pluck('id');

        PermisoOperacion::whereIn('operacion_id', $ids)->delete();
        Operacion::whereIn('id', $ids)->delete();
    }

    public function down(): void
    {
        // Catálogo aditivo por convención del proyecto: no se revierte.
    }
};
