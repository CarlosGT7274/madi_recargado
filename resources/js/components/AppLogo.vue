<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
defineProps<{
    title?: string;
    subtitle?: string;
}>();
</script>
<template>
    <div class="flex aspect-square size-9 items-center justify-center rounded-md bg-white p-0.5">
        <AppLogoIcon class="size-full" />
    </div>
    <div class="ml-1 grid flex-1 text-left group-data-[collapsible=icon]:hidden">
        <span class="truncate text-base font-bold leading-tight tracking-tight">{{ title }}</span>
        <span v-if="subtitle" class="truncate text-xs leading-tight text-sidebar-foreground/70">{{ subtitle }}</span>
    </div>
</template>
