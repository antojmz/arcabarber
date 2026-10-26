<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPercent } from '@/lib/money';
import { create, edit, index } from '@/routes/admin/barbers';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Barberos', href: index() }],
    },
});

defineProps<{
    barbers: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        commission_rate: string;
        is_active: boolean;
        month_services_count: number;
    }[];
}>();
</script>

<template>
    <Head title="Barberos" />

    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Barberos"
                description="Cuentas de acceso y porcentaje de comisión de cada barbero."
            />
            <Button as-child>
                <Link :href="create()"><Plus /> Nuevo barbero</Link>
            </Button>
        </div>

        <EmptyState
            v-if="barbers.length === 0"
            title="Aún no hay barberos"
            description="Crea la cuenta de cada barbero para que pueda registrar sus servicios."
        />

        <div v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="barber in barbers"
                :key="barber.id"
                :href="edit(barber.id)"
                class="rounded-xl border p-4 transition-colors hover:bg-accent/50"
                :class="{ 'opacity-60': !barber.is_active }"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ barber.name }}</p>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ barber.email }}
                        </p>
                        <p
                            v-if="barber.phone"
                            class="text-sm text-muted-foreground"
                        >
                            {{ barber.phone }}
                        </p>
                    </div>
                    <Badge
                        :variant="barber.is_active ? 'secondary' : 'outline'"
                    >
                        {{ barber.is_active ? 'Activo' : 'Inactivo' }}
                    </Badge>
                </div>
                <div class="mt-3 flex gap-6 text-sm">
                    <div>
                        <p class="text-xs text-muted-foreground">Comisión</p>
                        <p class="font-semibold">
                            {{ formatPercent(barber.commission_rate) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Servicios este mes
                        </p>
                        <p class="font-semibold tabular-nums">
                            {{ barber.month_services_count }}
                        </p>
                    </div>
                </div>
            </Link>
        </div>
    </div>
</template>
