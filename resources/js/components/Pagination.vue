<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types';

defineProps<{
    links: PaginationLink[];
}>();

function label(value: string): string {
    return value
        .replace('&laquo; Previous', '‹')
        .replace('Next &raquo;', '›')
        .replace('pagination.previous', '‹')
        .replace('pagination.next', '›');
}
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1"
    >
        <template v-for="link in links" :key="link.label">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="min-w-9 rounded-md border px-3 py-1.5 text-center text-sm transition-colors hover:bg-accent"
                :class="{
                    'border-primary bg-primary text-primary-foreground hover:bg-primary/90':
                        link.active,
                }"
            >
                {{ label(link.label) }}
            </Link>
            <span
                v-else
                class="min-w-9 rounded-md px-3 py-1.5 text-center text-sm text-muted-foreground"
            >
                {{ label(link.label) }}
            </span>
        </template>
    </nav>
</template>
