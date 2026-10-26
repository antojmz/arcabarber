<script setup lang="ts">
import { computed } from 'vue';
import type { PaymentMethodOption, ServiceCount, ServiceLine } from '@/types';

const props = defineProps<{
    services: ServiceLine[];
    counts: ServiceCount[];
    paymentMethods: PaymentMethodOption[];
}>();

const usedMethods = computed(() =>
    props.paymentMethods.filter((method) =>
        props.counts.some((count) => count.payment_method_id === method.id),
    ),
);

function count(serviceId: number, methodId: number): number {
    return (
        props.counts.find(
            (row) =>
                row.service_id === serviceId &&
                row.payment_method_id === methodId,
        )?.services_count ?? 0
    );
}
</script>

<template>
    <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-sm">
            <thead
                class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
            >
                <tr>
                    <th class="px-4 py-2.5 font-medium">Servicio</th>
                    <th
                        v-for="method in usedMethods"
                        :key="method.id"
                        class="px-4 py-2.5 text-right font-medium"
                    >
                        {{ method.name }}
                    </th>
                    <th class="px-4 py-2.5 text-right font-medium">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr v-for="service in services" :key="service.service_id">
                    <td class="px-4 py-2.5 font-medium">
                        {{ service.service_name }}
                    </td>
                    <td
                        v-for="method in usedMethods"
                        :key="method.id"
                        class="px-4 py-2.5 text-right tabular-nums"
                    >
                        {{ count(service.service_id, method.id) || '–' }}
                    </td>
                    <td
                        class="px-4 py-2.5 text-right font-semibold tabular-nums"
                    >
                        {{ service.services_count }}
                    </td>
                </tr>
                <tr v-if="services.length === 0">
                    <td
                        :colspan="usedMethods.length + 2"
                        class="px-4 py-6 text-center text-muted-foreground"
                    >
                        Sin servicios registrados en este periodo.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
