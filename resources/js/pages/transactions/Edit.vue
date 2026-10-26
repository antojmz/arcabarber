<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TransactionForm from '@/components/transactions/TransactionForm.vue';
import { index, update } from '@/routes/transactions';
import type {
    BarberOption,
    PaymentMethodOption,
    ServiceOption,
    Transaction,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transacciones', href: index() },
            { title: 'Editar', href: '#' },
        ],
    },
});

defineProps<{
    transaction: Transaction;
    services: ServiceOption[];
    paymentMethods: PaymentMethodOption[];
    barbers: BarberOption[];
    currentBarber: BarberOption | null;
}>();
</script>

<template>
    <Head title="Editar transacción" />

    <div class="p-4 md:p-6">
        <Heading
            :title="`Editar transacción #${transaction.id}`"
            description="Si no cambias el barbero o el método de pago, se mantienen los porcentajes con los que se registró."
        />

        <TransactionForm
            :action="update(transaction.id)"
            :services="services"
            :payment-methods="paymentMethods"
            :barbers="barbers"
            :current-barber="currentBarber"
            :transaction="transaction"
            submit-label="Guardar cambios"
        />
    </div>
</template>
