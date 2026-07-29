import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export const Accion = {
    READ: 1,
    CREATE: 2,
    UPDATE: 4,
    DELETE: 8,
    ALL: 15,
} as const;

export function usePermissions() {
    const page = usePage();

    // The shared permissions map: { "ingenierias.levantamientos": 15, ... }
    const permisosMap = computed(() => (page.props.permisos as Record<string, number>) || {});

    function hasPermission(endpoint: string, accion: number): boolean {
        // Find the exact endpoint or the longest prefix
        // Since the backend 'puedePorEndpoint' allows prefix matching (e.g. if 'ingenierias' has 15, then 'ingenierias.plantas' is allowed)
        let matchedBitmask = 0;
        
        // Exact match first
        if (permisosMap.value[endpoint] !== undefined) {
            matchedBitmask = permisosMap.value[endpoint];
        } else {
            // Find prefix matches
            const prefixes = Object.keys(permisosMap.value)
                .filter(key => endpoint.startsWith(key + '.'))
                .sort((a, b) => b.length - a.length);
                
            if (prefixes.length > 0) {
                matchedBitmask = permisosMap.value[prefixes[0]];
            }
        }

        return (matchedBitmask & accion) === accion;
    }

    return {
        Accion,
        hasPermission,
    };
}
