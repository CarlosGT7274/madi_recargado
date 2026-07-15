import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: string | null;
    hijos: NavItem[];
    icon?: LucideIcon;
    isActive?: boolean;
};

export type MenuItem = {
    id: number;
    nombre: string;
    endpoint: string | null;
    padre_id: number | null;
    url: string | null;
    hijos: MenuItem[];
};
