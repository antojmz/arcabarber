<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import BarberBreakdown from '@/components/BarberBreakdown.vue';
import Heading from '@/components/Heading.vue';
import PaymentMethodBreakdown from '@/components/PaymentMethodBreakdown.vue';
import StatCard from '@/components/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateTime, formatRange } from '@/lib/dates';
import { formatMoney } from '@/lib/money';
import { index, markPaid } from '@/routes/admin/closings';
import type { BarberLine, CashClosing, PaymentMethodLine } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Historial de cierres', href: index() },
            { title: 'Detalle', href: '#' },
        ],
    },
});

defineProps<{
    closing: CashClosing & {
        notes: string | null;
        by_payment_method: PaymentMethodLine[];
        by_barber: BarberLine[];
    };
}>();

function print() {
    window.print();
}
</script>

<template>
    <Head :title="`Cierre #${closing.id}`" />

    <div class="space-y-6 p-4 md:p-6 print:p-0">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="`${closing.barber_name ? `Liquidación de ${closing.barber_name}` : 'Cierre general'} · ${closing.period_label}`"
                :description="
                    formatRange(closing.period_start, closing.period_end)
                "
            />
            <div class="flex flex-wrap gap-2 print:hidden">
                <Form
                    v-if="closing.barber_id"
                    v-bind="markPaid.form(closing.id)"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        :variant="closing.paid_at ? 'outline' : 'default'"
                        :disabled="processing"
                    >
                        {{
                            closing.paid_at
                                ? 'Marcar como pendiente'
                                : 'Marcar como pagado'
                        }}
                    </Button>
                </Form>
                <Button variant="outline" @click="print"
                    ><Printer /> Imprimir</Button
                >
            </div>
        </div>

        <div
            class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
        >
            <span>
                Cerrado por {{ closing.closed_by_name }}
                <template v-if="closing.created_at"
                    >el {{ formatDateTime(closing.created_at) }}</template
                >
            </span>
            <template v-if="closing.barber_id">
                <Badge v-if="closing.paid_at" variant="secondary"
                    >Pagado el {{ formatDateTime(closing.paid_at) }}</Badge
                >
                <Badge v-else variant="destructive">Pendiente de pago</Badge>
            </template>
        </div>

        <p
            v-if="closing.notes"
            class="rounded-lg border bg-muted/30 p-3 text-sm"
        >
            {{ closing.notes }}
        </p>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
            <StatCard label="Servicios" :value="closing.services_count" />
            <StatCard label="Bruto" :value="formatMoney(closing.gross_total)" />
            <StatCard
                label="IVA"
                tone="muted"
                :value="formatMoney(closing.iva_total)"
            />
            <StatCard
                label="Comisión barbero(s)"
                :value="formatMoney(closing.barber_total)"
            />
            <StatCard
                label="Propinas"
                :value="formatMoney(closing.tips_total)"
            />
            <StatCard
                v-if="closing.barber_id"
                label="Neto a pagar"
                tone="highlight"
                :value="formatMoney(closing.payout_total)"
            />
            <StatCard
                v-else
                label="Ganancia barbería"
                tone="positive"
                :value="formatMoney(closing.shop_total)"
            />
        </div>

        <section class="space-y-3">
            <h3 class="font-semibold">Desglose por método de pago</h3>
            <PaymentMethodBreakdown
                :lines="closing.by_payment_method"
                :totals="closing"
            />
        </section>

        <section v-if="!closing.barber_id" class="space-y-3">
            <h3 class="font-semibold">Productividad del equipo</h3>
            <BarberBreakdown :lines="closing.by_barber" />
        </section>
    </div>
</template>
