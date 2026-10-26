<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileBarChart, Plus, Wallet } from '@lucide/vue';
import BarberBreakdown from '@/components/BarberBreakdown.vue';
import Heading from '@/components/Heading.vue';
import StatCard from '@/components/StatCard.vue';
import RecentTransactions from '@/components/transactions/RecentTransactions.vue';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/money';
import { dashboard } from '@/routes';
import { barber as barberReport, general } from '@/routes/admin/reports';
import { create, index } from '@/routes/transactions';
import type { Summary, Totals, Transaction } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Panel', href: dashboard() }],
    },
});

defineProps<{
    today: Summary;
    week: Required<Totals>;
    month: Required<Totals>;
    recent: Transaction[];
}>();
</script>

<template>
    <Head title="Panel" />

    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Panel de la barbería"
                description="Resumen del día y accesos rápidos."
            />
            <div class="flex flex-wrap gap-2">
                <Button as-child size="lg">
                    <Link :href="create()"><Plus /> Registrar servicio</Link>
                </Button>
            </div>
        </div>

        <section class="space-y-3">
            <h3 class="font-semibold">Hoy</h3>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                <StatCard
                    label="Ingresado hoy"
                    tone="highlight"
                    :value="
                        formatMoney(
                            today.totals.gross_total + today.totals.tips_total,
                        )
                    "
                    hint="Servicios + propinas"
                    class="col-span-2 lg:col-span-1"
                />
                <StatCard
                    label="Servicios"
                    :value="today.totals.services_count"
                />
                <StatCard
                    label="Comisión ATM"
                    tone="muted"
                    :value="formatMoney(today.totals.atm_fee_total)"
                />
                <StatCard
                    label="A pagar barberos"
                    :value="formatMoney(today.totals.payout_total)"
                />
                <StatCard
                    label="Ganancia barbería"
                    tone="positive"
                    :value="formatMoney(today.totals.shop_total)"
                />
            </div>
        </section>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border p-4">
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Esta semana
                </p>
                <p class="mt-1 text-xl font-semibold tabular-nums">
                    {{ formatMoney(week.gross_total) }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ week.services_count }} servicios · Barbería
                    {{ formatMoney(week.shop_total) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Este mes
                </p>
                <p class="mt-1 text-xl font-semibold tabular-nums">
                    {{ formatMoney(month.gross_total) }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ month.services_count }} servicios · Barbería
                    {{ formatMoney(month.shop_total) }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
            <section class="space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-semibold">Equipo hoy</h3>
                    <div class="flex gap-1">
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="barberReport()"
                                ><Wallet /> Liquidaciones</Link
                            >
                        </Button>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="general()"
                                ><FileBarChart /> Cierre general</Link
                            >
                        </Button>
                    </div>
                </div>
                <BarberBreakdown :lines="today.by_barber" />
            </section>

            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">Últimos registros</h3>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="index()">Ver todos</Link>
                    </Button>
                </div>
                <RecentTransactions :transactions="recent" show-barber />
            </section>
        </div>
    </div>
</template>
