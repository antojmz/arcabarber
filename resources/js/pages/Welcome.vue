<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
</script>

<template>
    <Head title="Bienvenido" />

    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-background p-6 text-foreground"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 top-0 h-[28rem] bg-[radial-gradient(ellipse_at_top,var(--color-gold)_0%,transparent_65%)] opacity-25 dark:opacity-10"
        />

        <main
            class="relative flex w-full max-w-md flex-col items-center gap-8 text-center"
        >
            <img
                src="/images/logo-completo.jpg"
                alt="Arca Barber"
                width="720"
                height="720"
                class="aspect-[4/3] w-full rounded-2xl object-cover shadow-xl ring-1 shadow-black/15 ring-gold/30"
            />

            <div class="space-y-3">
                <p
                    class="flex items-center justify-center gap-3 text-xs tracking-[0.4em] text-gold uppercase"
                >
                    <span class="h-px w-8 bg-gold/60" />
                    Barbería
                    <span class="h-px w-8 bg-gold/60" />
                </p>
                <p class="text-muted-foreground">
                    Registro de servicios, comisiones y cierres de caja.
                </p>
            </div>

            <Button as-child size="lg" class="w-full sm:w-64">
                <Link v-if="$page.props.auth.user" :href="dashboard()"
                    >Ir a mi panel</Link
                >
                <Link v-else :href="login()">Iniciar sesión</Link>
            </Button>
        </main>
    </div>
</template>
