<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/dates';
import { formatPercent } from '@/lib/money';
import {
    create,
    destroy,
    edit,
    index,
    restore,
} from '@/routes/admin/payment-methods';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Métodos de pago', href: index() }],
    },
});

defineProps<{
    paymentMethods: {
        id: number;
        name: string;
        iva_rate: string;
        is_active: boolean;
        sort_order: number;
    }[];
    trashedPaymentMethods: {
        id: number;
        name: string;
        iva_rate: string;
        deleted_at: string;
    }[];
}>();
</script>

<template>
    <Head title="Métodos de pago" />

    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Métodos de pago"
                description="El IVA se descuenta del monto antes de repartir entre barbero y barbería."
            />
            <Button as-child>
                <Link :href="create()"><Plus /> Nuevo método</Link>
            </Button>
        </div>

        <EmptyState
            v-if="
                paymentMethods.length === 0 &&
                trashedPaymentMethods.length === 0
            "
            title="Aún no hay métodos de pago"
        />

        <div
            v-else-if="paymentMethods.length > 0"
            class="overflow-x-auto rounded-xl border"
        >
            <table class="w-full text-sm">
                <thead
                    class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Método</th>
                        <th class="px-4 py-2.5 text-right font-medium">IVA</th>
                        <th class="px-4 py-2.5 font-medium">Estado</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="method in paymentMethods" :key="method.id">
                        <td class="px-4 py-2.5">
                            <span class="font-medium">{{ method.name }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">
                            {{ formatPercent(method.iva_rate) }}
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

        <section v-if="trashedPaymentMethods.length > 0" class="space-y-3">
            <Heading
                variant="small"
                title="Eliminados"
                description="No aparecen al registrar servicios. Reactívalos para volver a usarlos."
            />
            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-2.5 font-medium">Método</th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                IVA
                            </th>
                            <th
                                class="hidden px-4 py-2.5 font-medium sm:table-cell"
                            >
                                Eliminado
                            </th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-muted-foreground">
                        <tr
                            v-for="method in trashedPaymentMethods"
                            :key="method.id"
                        >
                            <td class="px-4 py-2.5 font-medium">
                                {{ method.name }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums">
                                {{ formatPercent(method.iva_rate) }}
                            </td>
                            <td class="hidden px-4 py-2.5 sm:table-cell">
                                {{ formatDate(method.deleted_at) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex justify-end">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :data-test="`restore-payment-method-${method.id}`"
                                        @click="
                                            router.visit(restore(method.id), {
                                                preserveScroll: true,
                                            })
                                        "
                                    >
                                        Reactivar
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
