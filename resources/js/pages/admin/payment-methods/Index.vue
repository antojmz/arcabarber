<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { GripVertical, Plus } from '@lucide/vue';
import { nextTick, ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
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
    reorder,
    restore,
} from '@/routes/admin/payment-methods';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Métodos de pago', href: index() }],
    },
});

const props = defineProps<{
    paymentMethods: {
        id: number;
        name: string;
        iva_rate: string;
        is_active: boolean;
    }[];
    trashedPaymentMethods: {
        id: number;
        name: string;
        iva_rate: string;
        deleted_at: string;
    }[];
}>();

const orderedPaymentMethods = ref([...props.paymentMethods]);

watch(
    () => props.paymentMethods,
    (items) => {
        orderedPaymentMethods.value = [...items];
    },
);

/**
 * Persist the dragged order; it is the order used when registering.
 */
function saveOrder(): void {
    nextTick(() => {
        router.visit(reorder(), {
            data: { ids: orderedPaymentMethods.value.map((item) => item.id) },
            preserveScroll: true,
        });
    });
}
</script>

<template>
    <Head title="Métodos de pago" />

    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Métodos de pago"
                description="El IVA se descuenta del monto antes de repartir entre barbero y barbería. Arrastra las filas para cambiar el orden en que aparecen al registrar."
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
            <VueDraggable
                v-model="orderedPaymentMethods"
                target="tbody"
                handle=".drag-handle"
                :animation="150"
                @update="saveOrder"
            >
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted/50 text-left text-xs text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="w-10 px-2 py-2.5">
                                <span class="sr-only">Orden</span>
                            </th>
                            <th class="px-4 py-2.5 font-medium">Método</th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                IVA
                            </th>
                            <th class="px-4 py-2.5 font-medium">Estado</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="method in orderedPaymentMethods"
                            :key="method.id"
                        >
                            <td class="w-10 px-2 py-2.5">
                                <button
                                    type="button"
                                    class="drag-handle flex cursor-grab touch-none items-center text-muted-foreground hover:text-foreground active:cursor-grabbing"
                                    :aria-label="`Arrastrar ${method.name} para cambiar el orden`"
                                >
                                    <GripVertical class="size-4" />
                                </button>
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="font-medium">{{
                                    method.name
                                }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums">
                                {{ formatPercent(method.iva_rate) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <Badge
                                    :variant="
                                        method.is_active
                                            ? 'secondary'
                                            : 'outline'
                                    "
                                >
                                    {{
                                        method.is_active ? 'Activo' : 'Inactivo'
                                    }}
                                </Badge>
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link :href="edit(method.id)"
                                            >Editar</Link
                                        >
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
            </VueDraggable>
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
