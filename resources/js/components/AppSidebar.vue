<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, FolderGit2 } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { MenuItem, NavItem } from '@/types';

const page = usePage();

const appName = computed(() => page.props.name ?? 'MADI');
const currentUser = computed(() => page.props.auth?.user);

function normalizarMenu(items: MenuItem[] | undefined | null): MenuItem[] {
    return (items ?? []).map((item) => ({
        ...item,
        hijos: normalizarMenu(item.hijos),
    }));
}

const mainNavItems = computed<MenuItem[]>(() => {
    return normalizarMenu(page.props.menu as MenuItem[] | undefined);
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
        hijos: [],
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
        hijos: [],
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader
            class="border-b border-sidebar-border/60 px-3 py-4 group-data-[collapsible=icon]:px-2"
        >
            <Link
                :href="dashboard()"
                class="flex items-center overflow-hidden"
            >
                <AppLogo :title="appName" :subtitle="currentUser?.name" />
            </Link>
        </SidebarHeader>
        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>
        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
