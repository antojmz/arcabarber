<script setup lang="ts">
import { formatMoney } from '@/lib/money';
import type { PaymentMethodLine, Totals } from '@/types';

defineProps<{
    lines: PaymentMethodLine[];
    totals: Totals;
    showBarberColumns?: boolean;
}>();
</script>

<template>
    <div class="overflow-x-auto rounded-xl border">
        <table class="w-full min-w-[560px] text-sm">
            <thead
                class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
            >
                <tr>
                    <th class="px-4 py-2.5 font-medium">Método</th>
                    <th class="px-4 py-2.5 text-right font-medium">
                        Servicios
                    </th>
                    <th class="px-4 py-2.5 text-right font-medium">Bruto</th>
                    <th class="px-4 py-2.5 text-right font-medium">IVA</th>
                    <th class="px-4 py-2.5 text-right font-medium">Barbero</th>
                    <th class="px-4 py-2.5 text-right font-medium">Barbería</th>
                    <th class="px-4 py-2.5 text-right font-medium">Propinas</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="line in lines" :key="line.payment_method_id">
                    <td class="px-4 py-2.5 font-medium">
                        {{ line.payment_method_name }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ line.services_count }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(line.gross_total) }}
                    </td>
                    <td
                        class="px-4 py-2.5 text-right text-muted-foreground tabular-nums"
                    >
                        {{ formatMoney(line.iva_total) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(line.barber_total) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(line.shop_total) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(line.tips_total) }}
                    </td>
                </tr>
                <tr v-if="lines.length === 0">
                    <td
                        colspan="7"
                        class="px-4 py-6 text-center text-muted-foreground"
                    >
                        Sin servicios registrados en este periodo.
                    </td>
                </tr>
            </tbody>
            <tfoot
                v-if="lines.length > 0"
                class="border-t bg-muted/30 font-semibold"
            >
                <tr>
                    <td class="px-4 py-2.5">Total</td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ totals.services_count }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(totals.gross_total) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(totals.iva_total) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(totals.barber_total) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(totals.shop_total) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(totals.tips_total) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</template>
