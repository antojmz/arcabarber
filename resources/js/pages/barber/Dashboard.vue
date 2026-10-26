<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import StatCard from '@/components/StatCard.vue';
import RecentTransactions from '@/components/transactions/RecentTransactions.vue';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/money';
import { dashboard } from '@/routes';
import { production } from '@/routes/my';
import { create, index } from '@/routes/transactions';
import type { Summary, Totals, Transaction } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mi panel', href: dashboard() }],
    },
});

defineProps<{
    today: Summary;
    week: Required<Totals>;
    month: Required<Totals>;
    recent: Transaction[];
}>();

const page = usePage();
</script>

<template>
    <Head title="Mi panel" />

    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="`Hola, ${page.props.auth.user.name}`"
                description="Tu producción del día, la semana y el mes."
            />
            <Button as-child size="lg" class="w-full sm:w-auto">
                <Link :href="create()"><Plus /> Registrar servicio</Link>
            </Button>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <StatCard
                label="Servicios hoy"
                :value="today.totals.services_count"
            />
            <StatCard
                label="Propinas hoy"
                :value="formatMoney(today.totals.tips_total)"
            />
            <StatCard
                label="A recibir hoy"
                tone="highlight"
                :value="formatMoney(today.totals.payout_total)"
                class="col-span-2"
            />
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <Link
                :href="production({ query: { period: 'weekly' } })"
                class="rounded-xl border p-4 transition-colors hover:bg-accent/50"
            >
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Esta semana
                </p>
                <p class="mt-1 text-xl font-semibold tabular-nums">
                    {{ formatMoney(week.payout_total) }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ week.services_count }} servicios
                </p>
            </Link>
            <Link
                :href="production({ query: { period: 'monthly' } })"
                class="rounded-xl border p-4 transition-colors hover:bg-accent/50"
            >
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Este mes
                </p>
                <p class="mt-1 text-xl font-semibold tabular-nums">
                    {{ formatMoney(month.payout_total) }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ month.services_count }} servicios
                </p>
            </Link>
        </div>

        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold">Mis últimos servicios</h3>
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="index()">Ver historial</Link>
                </Button>
            </div>
            <RecentTransactions :transactions="recent" />
        </section>
    </div>
</template>
