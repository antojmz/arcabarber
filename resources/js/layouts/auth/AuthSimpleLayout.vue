<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-background p-6 md:p-10"
    >
        <!-- Warm spotlight, like the lights over the shop sign. -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 top-0 h-96 bg-[radial-gradient(ellipse_at_top,var(--color-gold)_0%,transparent_65%)] opacity-20 dark:opacity-10"
        />

        <div class="relative w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="home()" class="block">
                        <img
                            src="/images/logo.png"
                            alt="Arca Barber"
                            width="256"
                            height="256"
                            class="size-36 rounded-2xl object-cover shadow-lg ring-1 shadow-black/10 ring-gold/30"
                        />
                        <span class="sr-only">Arca Barber</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1
                            class="font-display text-2xl font-semibold tracking-wide"
                        >
                            {{ title }}
                        </h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
