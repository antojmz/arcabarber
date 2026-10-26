<script setup lang="ts">
import { formatMoney } from '@/lib/money';
import type { BarberLine } from '@/types';

defineProps<{
    lines: BarberLine[];
}>();
</script>

<template>
    <div class="overflow-x-auto rounded-xl border">
        <table class="w-full min-w-[560px] text-sm">
            <thead
                class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
            >
                <tr>
                    <th class="px-4 py-2.5 font-medium">Barbero</th>
                    <th class="px-4 py-2.5 text-right font-medium">
                        Servicios
                    </th>
                    <th class="px-4 py-2.5 text-right font-medium">Bruto</th>
                    <th class="px-4 py-2.5 text-right font-medium">Comisión</th>
                    <th class="px-4 py-2.5 text-right font-medium">Propinas</th>
                    <th class="px-4 py-2.5 text-right font-medium">A pagar</th>
                    <th class="px-4 py-2.5 text-right font-medium">
                        Para la barbería
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="line in lines" :key="line.barber_id">
                    <td class="px-4 py-2.5 font-medium">
                        {{ line.barber_name }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ line.services_count }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(line.gross_total) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(line.barber_total) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(line.tips_total) }}
                    </td>
                    <td
                        class="px-4 py-2.5 text-right font-semibold tabular-nums"
                    >
                        {{ formatMoney(line.barber_total + line.tips_total) }}
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ formatMoney(line.shop_total) }}
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
        </table>
    </div>
</template>
