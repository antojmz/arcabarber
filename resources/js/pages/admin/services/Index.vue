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
import { formatMoney } from '@/lib/money';
import {
    create,
    destroy,
    edit,
    index,
    reorder,
    restore,
} from '@/routes/admin/services';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Servicios', href: index() }],
    },
});

const props = defineProps<{
    services: {
        id: number;
        name: string;
        base_price: number;
        duration_minutes: number | null;
        is_active: boolean;
    }[];
    trashedServices: {
        id: number;
        name: string;
        base_price: number;
        deleted_at: string;
    }[];
}>();

const orderedServices = ref([...props.services]);

watch(
    () => props.services,
    (items) => {
        orderedServices.value = [...items];
    },
);

/**
 * Persist the dragged order; it is the order used when registering.
 */
function saveOrder(): void {
    nextTick(() => {
        router.visit(reorder(), {
            data: { ids: orderedServices.value.map((item) => item.id) },
            preserveScroll: true,
        });
    });
}
</script>

<template>
    <Head title="Servicios" />

    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Servicios"
                description="Precio base que se autocompleta al registrar cada servicio. Arrastra las filas para cambiar el orden en que aparecen al registrar."
            />
            <Button as-child>
                <Link :href="create()"><Plus /> Nuevo servicio</Link>
            </Button>
        </div>

        <EmptyState
            v-if="services.length === 0 && trashedServices.length === 0"
            title="Aún no hay servicios"
            description="Crea los servicios que ofrece la barbería con su precio base."
        />

        <div
            v-else-if="services.length > 0"
            class="overflow-x-auto rounded-xl border"
        >
            <VueDraggable
                v-model="orderedServices"
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
                            <th class="px-4 py-2.5 font-medium">Servicio</th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                Precio base
                            </th>
                            <th
                                class="hidden px-4 py-2.5 text-right font-medium sm:table-cell"
                            >
                                Duración
                            </th>
                            <th class="px-4 py-2.5 font-medium">Estado</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="service in orderedServices"
                            :key="service.id"
                        >
                            <td class="w-10 px-2 py-2.5">
                                <button
                                    type="button"
                                    class="drag-handle flex cursor-grab touch-none items-center text-muted-foreground hover:text-foreground active:cursor-grabbing"
                                    :aria-label="`Arrastrar ${service.name} para cambiar el orden`"
                                >
                                    <GripVertical class="size-4" />
                                </button>
                            </td>
                            <td class="px-4 py-2.5 font-medium">
                                {{ service.name }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums">
                                {{ formatMoney(service.base_price) }}
                            </td>
                            <td
                                class="hidden px-4 py-2.5 text-right text-muted-foreground sm:table-cell"
                            >
                                {{
                                    service.duration_minutes
                                        ? `${service.duration_minutes} min`
                                        : '–'
                                }}
                            </td>
                            <td class="px-4 py-2.5">
                                <Badge
                                    :variant="
                                        service.is_active
                                            ? 'secondary'
                                            : 'outline'
                                    "
                                >
                                    {{
                                        service.is_active
                                            ? 'Activo'
                                            : 'Inactivo'
                                    }}
                                </Badge>
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link :href="edit(service.id)"
                                            >Editar</Link
                                        >
                                    </Button>
                                    <ConfirmDeleteButton
                                        :action="destroy(service.id)"
                                        :title="`¿Eliminar ${service.name}?`"
                                        description="Dejará de aparecer al registrar servicios. Las transacciones ya registradas no se modifican."
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </VueDraggable>
        </div>

        <section v-if="trashedServices.length > 0" class="space-y-3">
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
                            <th class="px-4 py-2.5 font-medium">Servicio</th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                Precio base
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
                            v-for="service in trashedServices"
                            :key="service.id"
                        >
                            <td class="px-4 py-2.5 font-medium">
                                {{ service.name }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums">
                                {{ formatMoney(service.base_price) }}
                            </td>
                            <td class="hidden px-4 py-2.5 sm:table-cell">
                                {{ formatDate(service.deleted_at) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex justify-end">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :data-test="`restore-service-${service.id}`"
                                        @click="
                                            router.visit(restore(service.id), {
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
