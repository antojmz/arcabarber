<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPercent } from '@/lib/money';
import { create, destroy, edit, index } from '@/routes/admin/payment-methods';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Métodos de pago', href: index() }],
    },
});

defineProps<{
    paymentMethods: {
        id: number;
        name: string;
        code: string;
        atm_fee_rate: string;
        is_active: boolean;
        sort_order: number;
    }[];
}>();
</script>

<template>
    <Head title="Métodos de pago" />

    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Métodos de pago"
                description="La comisión ATM se descuenta del monto antes de repartir entre barbero y barbería."
            />
            <Button as-child>
                <Link :href="create()"><Plus /> Nuevo método</Link>
            </Button>
        </div>

        <EmptyState
            v-if="paymentMethods.length === 0"
            title="Aún no hay métodos de pago"
        />

        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead
                    class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Método</th>
                        <th class="px-4 py-2.5 text-right font-medium">
                            Comisión ATM
                        </th>
                        <th class="px-4 py-2.5 font-medium">Estado</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="method in paymentMethods" :key="method.id">
                        <td class="px-4 py-2.5">
                            <span class="font-medium">{{ method.name }}</span>
                            <span class="ml-2 text-xs text-muted-foreground">{{
                                method.code
                            }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">
                            {{ formatPercent(method.atm_fee_rate) }}
                        </td>
                        <td class="px-4 py-2.5">
                            <Badge
                                :variant="
                                    method.is_active ? 'secondary' : 'outline'
                                "
                            >
                                {{ method.is_active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex justify-end gap-1">
                                <Button variant="ghost" size="sm" as-child>
                                    <Link :href="edit(method.id)">Editar</Link>
                                </Button>
                                <ConfirmDeleteButton
                                    :action="destroy(method.id)"
                                    :title="`¿Eliminar ${method.name}?`"
                                    description="Dejará de aparecer al registrar servicios. Las transacciones ya registradas no se modifican."
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
