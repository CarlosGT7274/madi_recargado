import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Accion, type OperacionClave } from '@/lib/permisos';

interface PagePropsConPermisos {
    permisos?: Record<string, OperacionClave[]>;
}

export function usePermissions() {
    const page = usePage<PagePropsConPermisos>();

    const permisos = computed<Record<string, OperacionClave[]>>(() => page.props.permisos ?? {});

    function hasPermission(endpoint: string, operacion: OperacionClave): boolean {
        return permisos.value[endpoint]?.includes(operacion) ?? false;
    }

    return { hasPermission, permisos, Accion };
}
