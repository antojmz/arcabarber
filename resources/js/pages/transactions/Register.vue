<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TransactionForm from '@/components/transactions/TransactionForm.vue';
import { create, store } from '@/routes/transactions';
import type { BarberOption, PaymentMethodOption, ServiceOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Registrar servicio', href: create() }],
    },
});

defineProps<{
    services: ServiceOption[];
    paymentMethods: PaymentMethodOption[];
    barbers: BarberOption[];
    currentBarber: BarberOption | null;
}>();
</script>

<template>
    <Head title="Registrar servicio" />

    <div class="p-4 md:p-6">
        <Heading
            title="Registrar servicio"
            description="Selecciona el servicio y el método de pago; el reparto se calcula automáticamente."
        />

        <TransactionForm
            :action="store()"
            :services="services"
            :payment-methods="paymentMethods"
            :barbers="barbers"
            :current-barber="currentBarber"
            submit-label="Registrar servicio"
        />
    </div>
</template>
