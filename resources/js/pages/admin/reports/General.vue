<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import BarberBreakdown from '@/components/BarberBreakdown.vue';
import ClosePeriodDialog from '@/components/ClosePeriodDialog.vue';
import Heading from '@/components/Heading.vue';
import PaymentMethodBreakdown from '@/components/PaymentMethodBreakdown.vue';
import PeriodFilter from '@/components/PeriodFilter.vue';
import StatCard from '@/components/StatCard.vue';
import { formatRange } from '@/lib/dates';
import { formatMoney } from '@/lib/money';
import { general } from '@/routes/admin/reports';
import type { DateRange, Summary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Cierre general', href: general() }],
    },
});

defineProps<{
    filters: { period: string; date: string };
    range: DateRange;
    summary: Summary;
    closing: { id: number; created_at: string | null } | null;
}>();
</script>

<template>
    <Head title="Cierre general" />

    <div class="space-y-6 p-4 md:p-6">
        <Heading
            title="Cierre general del local"
            description="Dinero total ingresado, desglose por método de pago y productividad del equipo."
        />

        <PeriodFilter
            :url="general.url()"
            :period="filters.period"
            :date="filters.date"
        />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground">
                {{ formatRange(range.start, range.end) }}
            </p>
            <ClosePeriodDialog
                :period="filters.period"
                :date="filters.date"
                :range="range"
                :closing="closing"
            />
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
            <StatCard
                label="Total ingresado"
                tone="highlight"
                :value="
                    formatMoney(
                        summary.totals.gross_total + summary.totals.tips_total,
                    )
                "
                hint="Servicios + propinas"
                class="col-span-2 lg:col-span-1"
            />
            <StatCard
                label="Servicios"
                :value="summary.totals.services_count"
            />
            <StatCard
                label="Bruto servicios"
                :value="formatMoney(summary.totals.gross_total)"
            />
            <StatCard
                label="Comisión ATM"
                tone="muted"
                :value="formatMoney(summary.totals.atm_fee_total)"
            />
            <StatCard
                label="A pagar barberos"
                :value="formatMoney(summary.totals.payout_total)"
                hint="Comisiones + propinas"
            />
            <StatCard
                label="Ganancia barbería"
                tone="positive"
                :value="formatMoney(summary.totals.shop_total)"
            />
        </div>

        <section class="space-y-3">
            <h3 class="font-semibold">Ingresos por método de pago</h3>
            <PaymentMethodBreakdown
                :lines="summary.by_payment_method"
                :totals="summary.totals"
            />
        </section>

        <section class="space-y-3">
            <h3 class="font-semibold">Productividad del equipo</h3>
            <BarberBreakdown :lines="summary.by_barber" />
        </section>
    </div>
</template>
