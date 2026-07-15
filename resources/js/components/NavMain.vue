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
import { toUrl } from '@/lib/utils';
import type { MenuItem } from '@/types';

const props = defineProps<{ items: MenuItem[] }>();
const { currentUrl } = useCurrentUrl();

function normalize(path: string): string {
    return path.replace(/\/+$/, '') || '/';
}

function pathOf(url: MenuItem['url']): string {
    const raw = toUrl(url ?? '');
    const path = raw.startsWith('http') ? new URL(raw).pathname : raw;
    return normalize(path);
}

// Active when the current path equals the target OR is a nested route of it
// (e.g. `/roles/1` is still "under" `/roles`), using segment boundaries so
// `/roles` does not falsely match `/roles-archive`.
function isActive(url: MenuItem['url']): boolean {
    if (!url) return false;
    const target = pathOf(url);
    const current = normalize(currentUrl.value || '/');
    return current === target || current.startsWith(`${target}/`);
}

function isParentActive(item: MenuItem): boolean {
    return isActive(item.url) || item.hijos.some((hijo) => isActive(hijo.url));
}

// Controlled open state: auto-expands the module that owns the current route
// while still letting the user toggle sections manually.
const openMap = reactive<Record<string | number, boolean>>({});

watch(
    currentUrl,
    () => {
        for (const item of props.items) {
            if (item.hijos.length && isParentActive(item)) {
                openMap[item.id] = true;
            }
        }
    },
    { immediate: true },
);
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.id">
                <Collapsible
                    v-if="item.hijos.length"
                    as-child
                    :open="openMap[item.id] ?? isParentActive(item)"
                    class="group/collapsible"
                    @update:open="(value) => (openMap[item.id] = value)"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton :tooltip="item.nombre" :is-active="isParentActive(item)">
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
                                        :is-active="isActive(hijo.url)"
                                    >
                                        <Link :href="hijo.url ?? '#'">{{ hijo.nombre }}</Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <SidebarMenuItem v-else>
                    <SidebarMenuButton as-child :is-active="isActive(item.url)" :tooltip="item.nombre">
                        <Link :href="item.url ?? '#'">{{ item.nombre }}</Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
