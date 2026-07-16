<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name ?? '');
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-5 fill-current text-sidebar-primary-foreground" />
    </div>
    <div class="ml-1 grid flex-1 text-left">
        <span class="truncate text-base font-bold leading-tight tracking-tight">MADI</span>
        <span v-if="userName" class="truncate text-xs leading-tight text-sidebar-foreground/70">{{ userName }}</span>
    </div>
</template>
