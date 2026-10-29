<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatMoney } from '@/lib/money';
import { index, store, update } from '@/routes/admin/payment-methods';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Métodos de pago', href: index() },
            { title: 'Formulario', href: '#' },
        ],
    },
});

const props = defineProps<{
    paymentMethod: {
        id: number;
        name: string;
        iva_rate: string;
        is_active: boolean;
        sort_order: number;
    } | null;
}>();

const form = useForm({
    name: props.paymentMethod?.name ?? '',
    iva_rate: props.paymentMethod ? Number(props.paymentMethod.iva_rate) : 0,
    is_active: props.paymentMethod?.is_active ?? true,
    sort_order: props.paymentMethod?.sort_order ?? 0,
});

const example = computed(() =>
    Math.round((20000 * Number(form.iva_rate || 0)) / 100),
);

function submit() {
    form.submit(props.paymentMethod ? update(props.paymentMethod.id) : store());
}
</script>

<template>
    <Head
        :title="
            paymentMethod
                ? `Editar ${paymentMethod.name}`
                : 'Nuevo método de pago'
        "
    />

    <div class="max-w-2xl p-4 md:p-6">
        <Heading
            :title="
                paymentMethod
                    ? `Editar ${paymentMethod.name}`
                    : 'Nuevo método de pago'
            "
            description="Cambiar el IVA no altera los servicios ya registrados."
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="name">Nombre</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        required
                        placeholder="Ej: Débito"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="iva_rate">IVA (%)</Label>
                    <Input
                        id="iva_rate"
                        v-model.number="form.iva_rate"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                    />
                    <p class="text-xs text-muted-foreground">
                        Ej: un servicio de {{ formatMoney(20000) }} paga
                        {{ formatMoney(example) }} de IVA.
                    </p>
                    <InputError :message="form.errors.iva_rate" />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="sort_order">Orden en el registro</Label>
                    <Input
                        id="sort_order"
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                    />
                    <InputError :message="form.errors.sort_order" />
                </div>
                <div class="flex items-center gap-3 sm:col-span-2">
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
