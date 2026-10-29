<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import Pagination from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import VoidTransactionDialog from '@/components/transactions/VoidTransactionDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/dates';
import { formatMoney } from '@/lib/money';
import { create, edit, index } from '@/routes/transactions';
import type {
    BarberOption,
    Paginated,
    PaymentMethodOption,
    Totals,
    Transaction,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Transacciones', href: index() }],
    },
});

const props = defineProps<{
    transactions: Paginated<Transaction>;
    totals: Totals;
    filters: {
        from: string;
        to: string;
        barber_id: number | string | null;
        payment_method_id: number | string | null;
    };
    barbers: BarberOption[];
    paymentMethods: PaymentMethodOption[];
}>();

const page = usePage();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

const filters = ref({
    from: props.filters.from,
    to: props.filters.to,
    barber_id: props.filters.barber_id ?? '',
    payment_method_id: props.filters.payment_method_id ?? '',
});

watch(
    filters,
    (value) => {
        router.get(
            index.url(),
            {
                from: value.from,
                to: value.to < value.from ? value.from : value.to,
                barber_id: value.barber_id || undefined,
                payment_method_id: value.payment_method_id || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    },
    { deep: true },
);
</script>

<template>
    <Head title="Transacciones" />

    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="isAdmin ? 'Transacciones' : 'Mi historial'"
                :description="
                    isAdmin
                        ? 'Todos los servicios registrados en la barbería.'
                        : 'Los servicios que has registrado.'
                "
            />
            <Button as-child>
                <Link :href="create()"> <Plus /> Registrar servicio </Link>
            </Button>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="grid gap-1.5">
                <Label for="from">Desde</Label>
                <Input id="from" v-model="filters.from" type="date" />
            </div>
            <div class="grid gap-1.5">
                <Label for="to">Hasta</Label>
                <Input
                    id="to"
                    v-model="filters.to"
                    type="date"
                    :min="filters.from"
                />
            </div>
            <div v-if="isAdmin" class="grid gap-1.5">
                <Label for="barber">Barbero</Label>
                <NativeSelect id="barber" v-model="filters.barber_id">
                    <option value="">Todos</option>
                    <option
                        v-for="barber in barbers"
                        :key="barber.id"
                        :value="barber.id"
                    >
                        {{ barber.name }}
                    </option>
                </NativeSelect>
            </div>
            <div class="grid gap-1.5">
                <Label for="method">Método de pago</Label>
                <NativeSelect id="method" v-model="filters.payment_method_id">
                    <option value="">Todos</option>
                    <option
                        v-for="method in paymentMethods"
                        :key="method.id"
                        :value="method.id"
                    >
                        {{ method.name }}
                    </option>
                </NativeSelect>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <StatCard label="Servicios" :value="totals.services_count" />
            <StatCard label="Bruto" :value="formatMoney(totals.gross_total)" />
            <StatCard
                label="Comisión barbero"
                :value="formatMoney(totals.barber_total)"
            />
            <StatCard
                label="Propinas"
                :value="formatMoney(totals.tips_total)"
            />
            <StatCard
                v-if="isAdmin"
                label="Barbería"
                :value="formatMoney(totals.shop_total)"
                :hint="`IVA: ${formatMoney(totals.iva_total)}`"
                class="col-span-2 lg:col-span-1"
            />
            <StatCard
                v-else
                label="A recibir"
                tone="highlight"
                :value="formatMoney(totals.barber_total + totals.tips_total)"
                class="col-span-2 lg:col-span-1"
            />
        </div>

        <EmptyState
            v-if="transactions.data.length === 0"
            title="Sin transacciones"
            description="No hay servicios registrados con estos filtros."
        />

        <template v-else>
            <!-- Mobile -->
            <ul class="space-y-3 md:hidden">
                <li
                    v-for="transaction in transactions.data"
                    :key="transaction.id"
                    class="rounded-xl border p-4"
                    :class="{ 'opacity-60': transaction.voided }"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p
                                class="font-medium"
                                :class="{ 'line-through': transaction.voided }"
                            >
                                {{ transaction.service_name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDateTime(transaction.performed_at) }}
                                <template v-if="isAdmin"
                                    >· {{ transaction.barber_name }}</template
                                >
                                · {{ transaction.payment_method_name }}
                            </p>
                        </div>
                        <p class="font-semibold tabular-nums">
                            {{ formatMoney(transaction.amount) }}
                        </p>
                    </div>
                    <div
                        class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground tabular-nums"
                    >
                        <span
                            >Barbero
                            {{ formatMoney(transaction.barber_amount) }}</span
                        >
                        <span v-if="transaction.tip"
                            >Propina {{ formatMoney(transaction.tip) }}</span
                        >
                        <span v-if="isAdmin"
                            >Barbería
                            {{ formatMoney(transaction.shop_amount) }}</span
                        >
                        <span v-if="transaction.iva_amount"
                            >IVA {{ formatMoney(transaction.iva_amount) }}</span
                        >
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <Badge v-if="transaction.voided" variant="destructive"
                            >Anulada</Badge
                        >
                        <Badge
                            v-else-if="transaction.locked"
                            variant="secondary"
                            >Cerrada</Badge
                        >
                        <span
                            v-if="transaction.voided && transaction.void_reason"
                            class="text-xs text-muted-foreground"
                        >
                            {{ transaction.void_reason }}
                        </span>
                        <template v-if="transaction.can_update">
                            <Button variant="ghost" size="sm" as-child>
                                <Link :href="edit(transaction.id)">Editar</Link>
                            </Button>
                            <VoidTransactionDialog :transaction="transaction" />
                        </template>
                    </div>
                </li>
            </ul>

            <!-- Desktop -->
            <div class="hidden overflow-x-auto rounded-xl border md:block">
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-2.5 font-medium">Fecha</th>
                            <th v-if="isAdmin" class="px-4 py-2.5 font-medium">
                                Barbero
                            </th>
                            <th class="px-4 py-2.5 font-medium">Servicio</th>
                            <th class="px-4 py-2.5 font-medium">Pago</th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                Monto
                            </th>
                            <th
                                v-if="isAdmin"
                                class="px-4 py-2.5 text-right font-medium"
                            >
                                IVA
                            </th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                Barbero
                            </th>
                            <th
                                v-if="isAdmin"
                                class="px-4 py-2.5 text-right font-medium"
                            >
                                Barbería
                            </th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                Propina
                            </th>
                            <th class="px-4 py-2.5 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="transaction in transactions.data"
                            :key="transaction.id"
                            :class="{
                                'text-muted-foreground': transaction.voided,
                            }"
                        >
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                {{ formatDateTime(transaction.performed_at) }}
                            </td>
                            <td v-if="isAdmin" class="px-4 py-2.5">
                                {{ transaction.barber_name }}
                            </td>
                            <td class="px-4 py-2.5">
                                <span
                                    :class="{
                                        'line-through': transaction.voided,
                                    }"
                                >
                                    {{ transaction.service_name }}
                                </span>
                                <p
                                    v-if="transaction.notes"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ transaction.notes }}
                                </p>
                                <p
                                    v-if="
                                        transaction.voided &&
                                        transaction.void_reason
                                    "
                                    class="text-xs text-destructive"
                                >
                                    Anulada: {{ transaction.void_reason }}
                                </p>
                            </td>
                            <td class="px-4 py-2.5">
                                {{ transaction.payment_method_name }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums">
                                {{ formatMoney(transaction.amount) }}
                            </td>
                            <td
                                v-if="isAdmin"
                                class="px-4 py-2.5 text-right text-muted-foreground tabular-nums"
                            >
                                {{ formatMoney(transaction.iva_amount) }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums">
                                {{ formatMoney(transaction.barber_amount) }}
                            </td>
                            <td
                                v-if="isAdmin"
                                class="px-4 py-2.5 text-right tabular-nums"
                            >
                                {{ formatMoney(transaction.shop_amount) }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums">
                                {{
                                    transaction.tip
                                        ? formatMoney(transaction.tip)
                                        : '–'
                                }}
                            </td>
                            <td class="px-4 py-2.5">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <Badge
                                        v-if="transaction.voided"
                                        variant="destructive"
                                        >Anulada</Badge
                                    >
                                    <Badge
                                        v-else-if="transaction.locked"
                                        variant="secondary"
                                        >Cerrada</Badge
                                    >
                                    <template v-if="transaction.can_update">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            as-child
                                        >
                                            <Link :href="edit(transaction.id)"
                                                >Editar</Link
                                            >
                                        </Button>
                                        <VoidTransactionDialog
                                            :transaction="transaction"
                                        />
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="transactions.links" />
        </template>
    </div>
</template>
