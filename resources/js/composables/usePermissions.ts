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
        const parts = endpoint.split('.');
        while (parts.length > 0) {
            const currentEndpoint = parts.join('.');
            if (permisos.value[currentEndpoint]) {
                return permisos.value[currentEndpoint].includes(operacion);
            }
            parts.pop();
        }
        return false;
    }

    return { hasPermission, permisos, Accion };
}
