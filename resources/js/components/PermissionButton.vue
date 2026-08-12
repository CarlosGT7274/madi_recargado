<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

type Props = {
    endpoint: string;
    accion: string;
    href?: NonNullable<InertiaLinkProps['href']>;
    variant?: 'default' | 'outline' | 'secondary' | 'ghost' | 'destructive' | 'link';
    size?: 'default' | 'sm' | 'lg' | 'icon';
    disabled?: boolean;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
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
        <Button :variant="variant" :size="size" :disabled="disabled" :class="cn(props.class)">
            <slot />
        </Button>
    </Link>
    <Button v-else-if="!href && hasPermission(endpoint, accion)" :variant="variant" :size="size" :disabled="disabled"
        :class="cn(props.class)" @click="$emit('click')">
        <slot />
    </Button>
</template>
