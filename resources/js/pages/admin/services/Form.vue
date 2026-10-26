<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store, update } from '@/routes/admin/services';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Servicios', href: index() },
            { title: 'Formulario', href: '#' },
        ],
    },
});

const props = defineProps<{
    service: {
        id: number;
        name: string;
        base_price: number;
        duration_minutes: number | null;
        is_active: boolean;
        sort_order: number;
    } | null;
}>();

const form = useForm({
    name: props.service?.name ?? '',
    base_price: props.service?.base_price ?? (undefined as number | undefined),
    duration_minutes:
        props.service?.duration_minutes ?? (undefined as number | undefined),
    is_active: props.service?.is_active ?? true,
    sort_order: props.service?.sort_order ?? 0,
});

function submit() {
    form.submit(props.service ? update(props.service.id) : store());
}
</script>

<template>
    <Head :title="service ? `Editar ${service.name}` : 'Nuevo servicio'" />

    <div class="max-w-2xl p-4 md:p-6">
        <Heading
            :title="service ? `Editar ${service.name}` : 'Nuevo servicio'"
            description="Cambiar el precio no altera los servicios ya registrados."
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="name">Nombre</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        required
                        placeholder="Ej: Corte + Barba"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="base_price">Precio base (CLP)</Label>
                    <Input
                        id="base_price"
                        v-model.number="form.base_price"
                        type="number"
                        min="0"
                        step="100"
                        required
                    />
                    <InputError :message="form.errors.base_price" />
                </div>
                <div class="grid gap-2">
                    <Label for="duration_minutes"
                        >Duración en minutos (opcional)</Label
                    >
                    <Input
                        id="duration_minutes"
                        v-model.number="form.duration_minutes"
                        type="number"
                        min="1"
                    />
                    <InputError :message="form.errors.duration_minutes" />
                </div>
                <div class="grid gap-2">
                    <Label for="sort_order">Orden en el registro</Label>
                    <Input
                        id="sort_order"
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                    />
                    <InputError :message="form.errors.sort_order" />
                </div>
                <div class="flex items-center gap-3 self-start pt-7">
                    <Checkbox id="is_active" v-model="form.is_active" />
                    <Label for="is_active">Disponible para registrar</Label>
                </div>
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing"
                    >Guardar</Button
                >
                <Button variant="outline" as-child>
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
