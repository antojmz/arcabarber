<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    label: string;
    value: string | number;
    hint?: string;
    tone?: 'default' | 'positive' | 'muted' | 'highlight';
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div
        :class="
            cn(
                'rounded-xl border bg-card p-4 text-card-foreground shadow-xs',
                props.tone === 'highlight' &&
                    'border-gold/50 bg-gradient-to-br from-gold/15 to-primary/10',
                props.class,
            )
        "
    >
        <p
            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
        >
            {{ label }}
        </p>
        <p
            :class="
                cn(
                    'mt-1 text-2xl font-semibold tabular-nums',
                    props.tone === 'positive' &&
                        'text-emerald-700 dark:text-emerald-400',
                    props.tone === 'highlight' && 'text-primary',
                    props.tone === 'muted' && 'text-muted-foreground',
                )
            "
        >
            {{ value }}
        </p>
        <p v-if="hint" class="mt-1 text-xs text-muted-foreground">
            {{ hint }}
        </p>
    </div>
</template>
