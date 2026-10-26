<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import PaymentMethodBreakdown from '@/components/PaymentMethodBreakdown.vue';
import PeriodFilter from '@/components/PeriodFilter.vue';
import ServiceCountTable from '@/components/ServiceCountTable.vue';
import StatCard from '@/components/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { formatRange } from '@/lib/dates';
import { formatMoney, formatPercent } from '@/lib/money';
import { production } from '@/routes/my';
import type {
    BarberOption,
    DateRange,
    PaymentMethodOption,
    ServiceCount,
    Summary,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mi producción', href: production() }],
    },
});

defineProps<{
    filters: { period: string; date: string };
    range: DateRange;
    barber: BarberOption;
    summary: Summary;
    serviceCounts: ServiceCount[];
    paymentMethods: PaymentMethodOption[];
    closings: {
        id: number;
        period_label: string;
        period_start: string;
        period_end: string;
        services_count: number;
        payout_total: number;
        paid_at: string | null;
    }[];
}>();
</script>

<template>
    <Head title="Mi producción" />

    <div class="space-y-6 p-4 md:p-6">
        <Heading
            title="Mi producción"
            :description="`Tu comisión actual es ${formatPercent(barber.commission_rate)} del monto neto (después de la comisión ATM).`"
        />

        <PeriodFilter
            :url="production.url()"
            :period="filters.period"
            :date="filters.date"
        />

        <p class="text-sm text-muted-foreground">
            {{ formatRange(range.start, range.end) }}
        </p>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <StatCard
                label="Servicios"
                :value="summary.totals.services_count"
            />
            <StatCard
                label="Bruto generado"
                :value="formatMoney(summary.totals.gross_total)"
            />
            <StatCard
                label="Mi comisión"
                :value="formatMoney(summary.totals.barber_total)"
            />
            <StatCard
                label="Propinas"
                :value="formatMoney(summary.totals.tips_total)"
            />
            <StatCard
                label="Total a recibir"
                tone="highlight"
                :value="formatMoney(summary.totals.payout_total)"
                class="col-span-2 lg:col-span-1"
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
            <h3 class="font-semibold">Por método de pago</h3>
            <PaymentMethodBreakdown
                :lines="summary.by_payment_method"
                :totals="summary.totals"
            />
        </section>

        <section v-if="closings.length > 0" class="space-y-3">
            <h3 class="font-semibold">Mis liquidaciones cerradas</h3>
            <ul class="divide-y rounded-xl border">
                <li
                    v-for="closing in closings"
                    :key="closing.id"
                    class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm"
                >
                    <div>
                        <p class="font-medium">
                            {{
                                formatRange(
                                    closing.period_start,
                                    closing.period_end,
                                )
                            }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ closing.period_label }} ·
                            {{ closing.services_count }} servicios
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold tabular-nums">{{
                            formatMoney(closing.payout_total)
                        }}</span>
                        <Badge
                            :variant="closing.paid_at ? 'secondary' : 'outline'"
                        >
                            {{ closing.paid_at ? 'Pagado' : 'Pendiente' }}
                        </Badge>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
