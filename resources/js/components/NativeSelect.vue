<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const model = defineModel<string | number | null>();

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <select
        v-model="model"
        :class="
            cn(
                'h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive md:text-sm dark:bg-input/30 [&>option]:bg-background',
                props.class,
            )
        "
    >
        <slot />
    </select>
</template>
