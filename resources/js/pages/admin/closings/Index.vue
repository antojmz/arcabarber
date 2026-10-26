<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { formatRange, periodOptions } from '@/lib/dates';
import { formatMoney } from '@/lib/money';
import { index, show } from '@/routes/admin/closings';
import type { BarberOption, CashClosing, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Historial de cierres', href: index() }],
    },
});

const props = defineProps<{
    closings: Paginated<CashClosing>;
    filters: { barber_id: string | null; period: string | null };
    barbers: BarberOption[];
}>();

const filters = ref({
    barber_id: props.filters.barber_id ?? '',
    period: props.filters.period ?? '',
});

watch(
    filters,
    (value) => {
        router.get(
            index.url(),
            {
                barber_id: value.barber_id || undefined,
                period: value.period || undefined,
            },
            { preserveState: true, replace: true },
        );
    },
    { deep: true },
);
</script>

<template>
    <Head title="Historial de cierres" />

    <div class="space-y-6 p-4 md:p-6">
        <Heading
            title="Historial de cierres"
            description="Snapshots guardados de cada cierre de caja y liquidación."
        />

        <div class="grid gap-3 sm:grid-cols-2 lg:w-2/3">
            <div class="grid gap-1.5">
                <Label for="closing-barber">Alcance</Label>
                <NativeSelect id="closing-barber" v-model="filters.barber_id">
                    <option value="">Todos</option>
                    <option value="general">Cierres generales</option>
                    <option
                        v-for="barber in barbers"
                        :key="barber.id"
                        :value="String(barber.id)"
                    >
                        {{ barber.name }}
                    </option>
                </NativeSelect>
            </div>
            <div class="grid gap-1.5">
                <Label for="closing-period">Periodo</Label>
                <NativeSelect id="closing-period" v-model="filters.period">
                    <option value="">Todos</option>
                    <option
                        v-for="option in periodOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </NativeSelect>
            </div>
        </div>

        <EmptyState
            v-if="closings.data.length === 0"
            title="Sin cierres"
            description="Los cierres se crean desde los reportes de liquidación o cierre general."
        />

        <template v-else>
            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full min-w-[640px] text-sm">
                    <thead
                        class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-2.5 font-medium">Periodo</th>
                            <th class="px-4 py-2.5 font-medium">Alcance</th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                Servicios
                            </th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                Bruto
                            </th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                A pagar
                            </th>
                            <th class="px-4 py-2.5 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="closing in closings.data"
                            :key="closing.id"
                            class="cursor-pointer transition-colors hover:bg-accent/50"
                            @click="router.visit(show(closing.id))"
                        >
                            <td class="px-4 py-2.5">
                                <Link
                                    :href="show(closing.id)"
                                    class="font-medium hover:underline"
                                    @click.stop
                                >
                                    {{
                                        formatRange(
                                            closing.period_start,
                                            closing.period_end,
                                        )
                                    }}
                                </Link>
                                <p class="text-xs text-muted-foreground">
                                    {{ closing.period_label }}
                                </p>
                            </td>
                            <td class="px-4 py-2.5">
                                {{ closing.barber_name ?? 'General' }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums">
                                {{ closing.services_count }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums">
                                {{ formatMoney(closing.gross_total) }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums">
                                {{ formatMoney(closing.payout_total) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <Badge
                                    v-if="!closing.barber_id"
                                    variant="outline"
                                    >General</Badge
                                >
                                <Badge
                                    v-else-if="closing.paid_at"
                                    variant="secondary"
                                    >Pagado</Badge
                                >
                                <Badge v-else variant="destructive"
                                    >Pendiente de pago</Badge
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="closings.links" />
        </template>
    </div>
</template>
