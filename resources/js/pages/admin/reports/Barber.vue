<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ClosePeriodDialog from '@/components/ClosePeriodDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import PaymentMethodBreakdown from '@/components/PaymentMethodBreakdown.vue';
import PeriodFilter from '@/components/PeriodFilter.vue';
import ServiceCountTable from '@/components/ServiceCountTable.vue';
import StatCard from '@/components/StatCard.vue';
import { formatRange } from '@/lib/dates';
import { formatMoney, formatPercent } from '@/lib/money';
import { barber as barberReport } from '@/routes/admin/reports';
import type {
    BarberOption,
    DateRange,
    PaymentMethodOption,
    ServiceCount,
    Summary,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Liquidación por barbero', href: barberReport() },
        ],
    },
});

defineProps<{
    filters: { period: string; date: string; barber_id: number | null };
    range: DateRange;
    barbers: BarberOption[];
    paymentMethods: PaymentMethodOption[];
    barber: BarberOption | null;
    summary: Summary | null;
    serviceCounts: ServiceCount[];
    closing: { id: number; created_at: string | null } | null;
}>();
</script>

<template>
    <Head title="Liquidación por barbero" />

    <div class="space-y-6 p-4 md:p-6">
        <Heading
            title="Liquidación por barbero"
            description="Producción, comisión y monto neto a pagar de un barbero en el periodo."
        />

        <PeriodFilter
            :url="barberReport.url()"
            :period="filters.period"
            :date="filters.date"
            :barbers="barbers"
            :barber-id="filters.barber_id"
        />

        <EmptyState
            v-if="!barber || !summary"
            title="Selecciona un barbero"
            description="Elige un barbero para ver su liquidación del periodo."
        />

        <template v-else>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold">{{ barber.name }}</h3>
                    <p class="text-sm text-muted-foreground">
                        {{ formatRange(range.start, range.end) }} · Comisión
                        {{ formatPercent(barber.commission_rate) }}
                    </p>
                </div>
                <ClosePeriodDialog
                    :period="filters.period"
                    :date="filters.date"
                    :range="range"
                    :barber-id="barber.id"
                    :barber-name="barber.name"
                    :closing="closing"
                />
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
                <StatCard
                    label="Servicios"
                    :value="summary.totals.services_count"
                />
                <StatCard
                    label="Bruto generado"
                    :value="formatMoney(summary.totals.gross_total)"
                />
                <StatCard
                    label="IVA"
                    tone="muted"
                    :value="formatMoney(summary.totals.iva_total)"
                />
                <StatCard
                    label="Comisión barbero"
                    :value="formatMoney(summary.totals.barber_total)"
                />
                <StatCard
                    label="Propinas"
                    :value="formatMoney(summary.totals.tips_total)"
                />
                <StatCard
                    label="Neto a pagar"
                    tone="highlight"
                    :value="formatMoney(summary.totals.payout_total)"
                    hint="Comisión + propinas"
                />
            </div>

            <section class="space-y-3">
                <h3 class="font-semibold">Servicios realizados</h3>
                <ServiceCountTable
                    :services="summary.by_service"
                    :counts="serviceCounts"
                    :payment-methods="paymentMethods"
                />
            </section>

            <section class="space-y-3">
                <h3 class="font-semibold">Desglose por método de pago</h3>
                <PaymentMethodBreakdown
                    :lines="summary.by_payment_method"
                    :totals="summary.totals"
                />
            </section>
        </template>
    </div>
</template>
