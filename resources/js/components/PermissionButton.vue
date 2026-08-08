<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';

type Props = {
    endpoint: string;
    accion: number;
    href?: NonNullable<InertiaLinkProps['href']>;
    variant?: 'default' | 'outline' | 'secondary' | 'ghost' | 'destructive' | 'link';
    size?: 'default' | 'sm' | 'lg' | 'icon';
    disabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
    size: 'default',
});

defineEmits<{
    (e: 'click'): void;
}>();

const { hasPermission } = usePermissions();
</script>

<template>
    <Link v-if="href && hasPermission(endpoint, accion)" :href="href">
        <Button :variant="variant" :size="size" :disabled="disabled">
            <slot />
        </Button>
    </Link>
    <Button v-else-if="!href && hasPermission(endpoint, accion)" :variant="variant" :size="size" :disabled="disabled"
        @click="$emit('click')">
        <slot />
    </Button>
</template>
