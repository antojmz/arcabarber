<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { formatDateTime } from '@/lib/dates';
import { formatMoney } from '@/lib/money';
import type { Transaction } from '@/types';

defineProps<{
    transactions: Transaction[];
    showBarber?: boolean;
}>();
</script>

<template>
    <ul class="divide-y rounded-xl border">
        <li
            v-for="transaction in transactions"
            :key="transaction.id"
            class="flex items-center justify-between gap-3 px-4 py-3 text-sm"
            :class="{ 'text-muted-foreground': transaction.voided }"
        >
            <div class="min-w-0">
                <p
                    class="truncate font-medium"
                    :class="{ 'line-through': transaction.voided }"
                >
                    {{ transaction.service_name }}
                    <template v-if="showBarber"
                        >· {{ transaction.barber_name }}</template
                    >
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ formatDateTime(transaction.performed_at) }} ·
                    {{ transaction.payment_method_name }}
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <Badge v-if="transaction.voided" variant="destructive"
                    >Anulada</Badge
                >
                <span class="font-semibold tabular-nums">{{
                    formatMoney(transaction.amount)
                }}</span>
            </div>
        </li>
        <li
            v-if="transactions.length === 0"
            class="px-4 py-6 text-center text-sm text-muted-foreground"
        >
            Aún no hay servicios registrados.
        </li>
    </ul>
</template>
