<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store, update } from '@/routes/admin/barbers';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Barberos', href: index() },
            { title: 'Formulario', href: '#' },
        ],
    },
});

const props = defineProps<{
    barber: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        commission_rate: string;
        is_active: boolean;
    } | null;
}>();

const form = useForm({
    name: props.barber?.name ?? '',
    email: props.barber?.email ?? '',
    phone: props.barber?.phone ?? '',
    commission_rate: props.barber ? Number(props.barber.commission_rate) : 40,
    is_active: props.barber?.is_active ?? true,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.submit(props.barber ? update(props.barber.id) : store(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head :title="barber ? `Editar ${barber.name}` : 'Nuevo barbero'" />

    <div class="max-w-2xl p-4 md:p-6">
        <Heading
            :title="barber ? `Editar ${barber.name}` : 'Nuevo barbero'"
            description="El barbero inicia sesión con este correo y contraseña. Solo verá sus propios servicios."
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="name">Nombre</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="email">Correo</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="phone">Teléfono (opcional)</Label>
                    <Input id="phone" v-model="form.phone" type="tel" />
                    <InputError :message="form.errors.phone" />
                </div>
                <div class="grid gap-2">
                    <Label for="commission_rate"
                        >Comisión del barbero (%)</Label
                    >
                    <Input
                        id="commission_rate"
                        v-model.number="form.commission_rate"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                    />
                    <p class="text-xs text-muted-foreground">
                        Porcentaje del monto neto (después del IVA) que recibe
                        el barbero. El resto queda para la barbería.
                    </p>
                    <InputError :message="form.errors.commission_rate" />
                </div>
                <div class="flex items-center gap-3 self-start pt-7">
                    <Checkbox id="is_active" v-model="form.is_active" />
                    <Label for="is_active">Cuenta activa</Label>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border p-4">
                <p class="text-sm font-medium">
                    {{
                        barber
                            ? 'Cambiar contraseña (opcional)'
                            : 'Contraseña de acceso'
                    }}
                </p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="password">Contraseña</Label>
                        <PasswordInput
                            id="password"
                            v-model="form.password"
                            :required="!barber"
                            autocomplete="new-password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >Confirmar contraseña</Label
                        >
                        <PasswordInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :required="!barber"
                            autocomplete="new-password"
                        />
                    </div>
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
