<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center rounded-xl border border-dashed px-6 py-10 text-center"
    >
        <p class="font-medium">{{ title }}</p>
        <p v-if="description" class="mt-1 text-sm text-muted-foreground">
            {{ description }}
        </p>
        <div class="mt-4 empty:hidden">
            <slot />
        </div>
    </div>
</template>
