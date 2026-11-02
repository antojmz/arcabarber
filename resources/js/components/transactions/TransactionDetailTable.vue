<script setup lang="ts">
import { computed } from 'vue';
import { formatDateTime } from '@/lib/dates';
import { formatMoney } from '@/lib/money';
import { joinServiceNames } from '@/lib/transactions';
import type { Totals, Transaction } from '@/types';

const props = defineProps<{
    transactions: Transaction[];
    totals: Totals;
    showBarber?: boolean;
}>();

// Date, services, method and the optional barber come before the amounts.
const leadingColumns = computed(() => (props.showBarber ? 4 : 3));
</script>

<template>
    <div class="overflow-x-auto rounded-xl border">
        <table class="w-full min-w-[860px] text-sm">
            <thead
                class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
            >
                <tr>
                    <th class="px-4 py-2.5 font-medium">Fecha</th>
                    <th class="px-4 py-2.5 font-medium">Servicios</th>
                    <th v-if="showBarber" class="px-4 py-2.5 font-medium">
                        Barbero
                    </th>
                    <th class="px-4 py-2.5 font-medium">Método</th>
                    <th class="px-4 py-2.5 text-right font-medium">Monto</th>
                    <th class="px-4 py-2.5 text-right font-medium">IVA</th>
                    <th class="px-4 py-2.5 text-right font-medium">Comisión</th>
                    <th class="px-4 py-2.5 text-right font-medium">Propina</th>
                    <th class="px-4 py-2.5 text-right font-medium">Barbería</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="transaction in transactions"
                    :key="transaction.id"
                    class="break-inside-avoid"
                >
                    <td
                        class="px-4 py-2.5 whitespace-nowrap text-muted-foreground tabular-nums"
                    >
                        {{ formatDateTime(transaction.performed_at) }}
                    </td>
                    <td class="px-4 py-2.5">
                        <span class="font-medium">{{
                            joinServiceNames(transaction.items)
                        }}</span>
                        <p
                            v-if="transaction.items.length > 1"
                            class="text-xs text-muted-foreground tabular-nums"
                        >
                            <template
                                v-for="(item, index) in transaction.items"
                                :key="item.service_id"
                            >
                                <template v-if="index > 0"> · </template>
                                {{ item.service_name }}
                                {{ formatMoney(item.amount) }}
                            </template>
                        </p>
                        <p
                            v-if="transaction.notes"
                            class="text-xs text-muted-foreground"
                        >
                            {{ transaction.notes }}
                        </p>
                    </td>
                    <td v-if="showBarber" class="px-4 py-2.5">
                        {{ transaction.barber_name }}
                    </td>
                    <td class="px-4 py-2.5">
                        {{ transaction.payment_method_name }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(transaction.amount) }}
                    </td>
                    <td
                        class="px-4 py-2.5 text-right text-muted-foreground tabular-nums"
                    >
                        {{ formatMoney(transaction.iva_amount) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(transaction.barber_amount) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(transaction.tip) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(transaction.shop_amount) }}
                    </td>
                </tr>
                <tr v-if="transactions.length === 0">
                    <td
                        :colspan="leadingColumns + 5"
                        class="px-4 py-6 text-center text-muted-foreground"
                    >
                        Sin transacciones en este periodo.
                    </td>
                </tr>
            </tbody>
            <tfoot
                v-if="transactions.length > 0"
                class="border-t bg-muted/30 font-semibold"
            >
                <tr>
                    <td class="px-4 py-2.5" :colspan="leadingColumns">
                        Total · {{ totals.services_count }}
                        {{
                            totals.services_count === 1
                                ? 'transacción'
                                : 'transacciones'
                        }}
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
                        {{ formatMoney(totals.tips_total) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(totals.shop_total) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</template>
