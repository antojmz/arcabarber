<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { periodOptions } from '@/lib/dates';
import type { BarberOption } from '@/types';

const props = defineProps<{
    url: string;
    period: string;
    date: string;
    barbers?: BarberOption[];
    barberId?: number | null;
}>();

const period = ref(props.period);
const date = ref(props.date);
const barberId = ref<number | string | null>(props.barberId ?? '');

watch([period, date, barberId], () => {
    router.get(
        props.url,
        {
            period: period.value,
            date: date.value,
            ...(props.barbers
                ? { barber_id: barberId.value || undefined }
                : {}),
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
});
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2 lg:flex lg:items-end">
        <div v-if="barbers" class="grid gap-1.5 lg:w-56">
            <Label for="filter-barber">Barbero</Label>
            <NativeSelect id="filter-barber" v-model="barberId">
                <option value="">Selecciona un barbero</option>
                <option
                    v-for="barber in barbers"
                    :key="barber.id"
                    :value="barber.id"
                >
                    {{ barber.name
                    }}{{ barber.is_active === false ? ' (inactivo)' : '' }}
                </option>
            </NativeSelect>
        </div>

        <div class="grid gap-1.5">
            <Label>Periodo</Label>
            <div class="inline-flex rounded-lg bg-muted p-1">
                <button
                    v-for="option in periodOptions"
                    :key="option.value"
                    type="button"
                    class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        period === option.value
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="period = option.value"
                >
                    {{ option.label }}
                </button>
            </div>
        </div>

        <div class="grid gap-1.5 lg:w-44">
            <Label for="filter-date">Fecha de referencia</Label>
            <Input id="filter-date" v-model="date" type="date" />
        </div>
    </div>
</template>
