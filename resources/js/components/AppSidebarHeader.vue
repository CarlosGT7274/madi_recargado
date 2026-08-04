<script setup lang="ts">
import { Bell, Clock } from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useNotificaciones } from '@/composables/useNotificaciones';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { notificaciones, total, marcarLeida, marcarTodasLeidas } = useNotificaciones();

const now = ref(new Date());
let timer: ReturnType<typeof setInterval> | undefined;

function formatoFechaHora(date: Date): string {
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    const hh = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');
    return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
}

onMounted(() => {
    timer = setInterval(() => {
        now.value = new Date();
    }, 1000 * 30);
});

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <header
        class="sticky top-0 z-10 flex h-16 shrink-0 items-center gap-3 border-b border-border/70 bg-background/80 px-4 backdrop-blur-sm transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-14 md:px-6">
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1 text-muted-foreground hover:text-foreground" />
            <div class="flex items-center gap-2 rounded-full bg-primary/10 py-1.5 pl-2 pr-3 text-primary">
                <Clock class="size-4" />
                <span class="text-sm font-semibold tabular-nums tracking-tight">
                    {{ formatoFechaHora(now) }}
                </span>
            </div>
        </div>
        <template v-if="breadcrumbs && breadcrumbs.length > 0">
            <div class="hidden md:block">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </template>
        <div class="ml-auto flex items-center gap-2">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon"
                        class="relative rounded-full text-muted-foreground hover:text-foreground"
                        aria-label="Notificaciones">
                        <Bell class="size-5" />
                        <span v-if="total > 0"
                            class="absolute right-1.5 top-1.5 size-2 rounded-full bg-primary ring-2 ring-background" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-80">
                    <DropdownMenuLabel class="flex items-center justify-between">
                        <span>Notificaciones</span>
                        <button v-if="total > 0" type="button" class="text-xs font-normal text-primary hover:underline"
                            @click="marcarTodasLeidas">
                            Marcar todas
                        </button>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem v-for="item in notificaciones" :key="item.id"
                        class="flex flex-col items-start gap-1 whitespace-normal" @click="marcarLeida(item.id)">
                        <span class="text-sm">{{ item.mensaje }}</span>
                        <span class="text-xs text-muted-foreground">{{ item.modulo }}</span>
                    </DropdownMenuItem>
                    <p v-if="!notificaciones.length" class="px-2 py-4 text-center text-sm text-muted-foreground">
                        No tienes notificaciones
                    </p>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
