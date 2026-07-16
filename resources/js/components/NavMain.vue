<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { reactive, watch } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { MenuItem } from '@/types';

const props = defineProps<{ items: MenuItem[] }>();
const { isCurrentUrl, isCurrentOrParentUrl, currentUrl } = useCurrentUrl();

function containsCurrentRoute(item: MenuItem): boolean {
    if (item.url && isCurrentOrParentUrl(item.url)) {
        return true;
    }
    return item.hijos.some(containsCurrentRoute);
}

const openState = reactive<Record<number, boolean>>({});

function seedOpenState(items: MenuItem[]) {
    items.forEach((item) => {
        if (item.hijos.length) {
            openState[item.id] = containsCurrentRoute(item);
            seedOpenState(item.hijos);
        }
    });
}
seedOpenState(props.items);

// Al navegar, si el grupo contiene la ruta activa, lo forzamos a abrir
// (pero no lo cerramos si el usuario lo abrió manualmente en otro contexto).
watch(currentUrl, () => {
    props.items.forEach((item) => {
        if (item.hijos.length && containsCurrentRoute(item)) {
            openState[item.id] = true;
        }
    });
});
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel
            class="text-[0.7rem] font-semibold uppercase tracking-wider text-sidebar-foreground/55"
            >Plataforma</SidebarGroupLabel
        >
        <SidebarMenu class="gap-1">
            <template v-for="item in items" :key="item.id">
                <Collapsible
                    v-if="item.hijos.length"
                    as-child
                    v-model:open="openState[item.id]"
                    class="group/collapsible"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                class="font-medium"
                                :is-active="containsCurrentRoute(item)"
                                :tooltip="item.nombre"
                            >
                                <span>{{ item.nombre }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="hijo in item.hijos" :key="hijo.id">
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="hijo.url ? isCurrentUrl(hijo.url) : false"
                                    >
                                        <Link :href="hijo.url ?? '#'">{{ hijo.nombre }}</Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <SidebarMenuItem v-else>
                    <SidebarMenuButton
                        class="font-medium"
                        as-child
                        :is-active="item.url ? isCurrentUrl(item.url) : false"
                        :tooltip="item.nombre"
                    >
                        <Link :href="item.url ?? '#'">{{ item.nombre }}</Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
