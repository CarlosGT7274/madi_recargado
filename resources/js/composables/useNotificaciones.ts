import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { toast } from 'vue-sonner';

export interface NotificacionItem {
    id: number;
    mensaje: string;
    modulo: string;
    tipo_entidad: string | null;
    entidad_id: number | null;
    fecha: string;
}

interface PageProps {
    auth: { user: { id: number } };
    notificaciones: NotificacionItem[];
}

const notificaciones = ref<NotificacionItem[]>([]);
let suscrito = false;

export function useNotificaciones() {
    const page = usePage<PageProps>();

    watch(
        () => page.props.notificaciones,
        (lista) => {
            notificaciones.value = lista ?? [];
        },
        { immediate: true },
    );

    if (!import.meta.env.SSR && !suscrito) {
        suscrito = true;

        useEcho<NotificacionItem>(
            `usuarios.${page.props.auth.user.id}`,
            '.notificacion.creada',
            (payload) => {
                notificaciones.value = [payload, ...notificaciones.value];
                toast.info(payload.mensaje);
            },
        );
    }

    const total = computed(() => notificaciones.value.length);

    function marcarLeida(id: number): void {
        const anterior = notificaciones.value;
        notificaciones.value = anterior.filter((item) => item.id !== id);

        router.patch(
            `/notificaciones/${id}/leida`,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => {
                    notificaciones.value = anterior;
                },
            },
        );
    }

    function marcarTodasLeidas(): void {
        const anterior = notificaciones.value;
        notificaciones.value = [];

        router.patch(
            '/notificaciones/leidas',
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => {
                    notificaciones.value = anterior;
                },
            },
        );
    }

    return { notificaciones, total, marcarLeida, marcarTodasLeidas };
}
