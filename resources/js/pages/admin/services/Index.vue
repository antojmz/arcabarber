<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import ConfirmDeleteButton from '@/components/ConfirmDeleteButton.vue';
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/money';
import { create, destroy, edit, index } from '@/routes/admin/services';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Servicios', href: index() }],
    },
});

defineProps<{
    services: {
        id: number;
        name: string;
        base_price: number;
        duration_minutes: number | null;
        is_active: boolean;
        sort_order: number;
    }[];
}>();
</script>

<template>
    <Head title="Servicios" />

    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Servicios"
                description="Precio base que se autocompleta al registrar cada servicio."
            />
            <Button as-child>
                <Link :href="create()"><Plus /> Nuevo servicio</Link>
            </Button>
        </div>

        <EmptyState
            v-if="services.length === 0"
            title="Aún no hay servicios"
            description="Crea los servicios que ofrece la barbería con su precio base."
        />

        <div v-else class="overflow-x-auto rounded-xl border">
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
                            class="hidden px-4 py-2.5 text-right font-medium sm:table-cell"
                        >
                            Duración
                        </th>
                        <th class="px-4 py-2.5 font-medium">Estado</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="service in services" :key="service.id">
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
                                    service.is_active ? 'secondary' : 'outline'
                                "
                            >
                                {{ service.is_active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex justify-end gap-1">
                                <Button variant="ghost" size="sm" as-child>
                                    <Link :href="edit(service.id)">Editar</Link>
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
        </div>
    </div>
</template>
