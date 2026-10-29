<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import type { RouteDefinition } from '@/wayfinder';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatMoney, formatPercent } from '@/lib/money';
import { calculateRevenueSplit } from '@/lib/revenueSplit';
import type {
    BarberOption,
    PaymentMethodOption,
    ServiceOption,
    Transaction,
} from '@/types';

const props = defineProps<{
    action: RouteDefinition<'post' | 'put'>;
    services: ServiceOption[];
    paymentMethods: PaymentMethodOption[];
    barbers: BarberOption[];
    currentBarber: BarberOption | null;
    transaction?: Transaction;
    submitLabel: string;
}>();

const isAdmin = computed(() => props.currentBarber === null);

type ServiceLine = { service_id: number; amount: number | undefined };

const form = useForm({
    barber_id: props.transaction?.barber_id ?? (null as number | null),
    services: (props.transaction?.items.map((item) => ({
        service_id: item.service_id,
        amount: item.amount,
    })) ?? []) as ServiceLine[],
    payment_method_id:
        props.transaction?.payment_method_id ?? (null as number | null),
    tip: props.transaction?.tip ?? (undefined as number | undefined),
    notes: props.transaction?.notes ?? '',
    performed_at: props.transaction?.performed_at.slice(0, 16) ?? '',
});

const tipPresets = [0, 1000, 2000, 3000, 5000];

const lines = computed(() =>
    form.services.flatMap((line) => {
        const service = props.services.find(
            (service) => service.id === line.service_id,
        );

        return service ? [{ line, service }] : [];
    }),
);
const selectedMethod = computed(() =>
    props.paymentMethods.find((method) => method.id === form.payment_method_id),
);
const selectedBarber = computed(() =>
    isAdmin.value
        ? props.barbers.find((barber) => barber.id === form.barber_id)
        : props.currentBarber,
);

function isSelected(serviceId: number): boolean {
    return form.services.some((line) => line.service_id === serviceId);
}

// The amount starts at the base price of the chosen service.
function toggleService(service: ServiceOption) {
    const line = { service_id: service.id, amount: service.base_price };

    if (isSelected(service.id)) {
        form.services = form.services.filter(
            (selected) => selected.service_id !== service.id,
        );
    } else {
        form.services = [...form.services, line];
    }
}

function amountOf(line: ServiceLine, service: ServiceOption): number {
    return isAdmin.value ? Number(line.amount ?? 0) : service.base_price;
}

const errors = computed(
    () => form.errors as Record<string, string | undefined>,
);

const servicesError = computed(
    () =>
        errors.value.services ??
        Object.entries(errors.value).find(([field]) =>
            /^services\.\d+\.service_id$/.test(field),
        )?.[1],
);

function amountError(index: number): string | undefined {
    return errors.value[`services.${index}.amount`];
}

const amount = computed(() =>
    lines.value.reduce(
        (total, { line, service }) => total + amountOf(line, service),
        0,
    ),
);

const ivaRate = computed(() => {
    if (
        props.transaction &&
        form.payment_method_id === props.transaction.payment_method_id
    ) {
        return props.transaction.iva_rate;
    }

    return Number(selectedMethod.value?.iva_rate ?? 0);
});

const commissionRate = computed(() => {
    if (props.transaction && form.barber_id === props.transaction.barber_id) {
        return props.transaction.barber_commission_rate;
    }

    return Number(selectedBarber.value?.commission_rate ?? 0);
});

// Each service is stored as its own transaction, so the split is rounded per service.
const split = computed(() =>
    lines.value.reduce(
        (total, { line, service }) => {
            const lineSplit = calculateRevenueSplit(
                amountOf(line, service),
                ivaRate.value,
                commissionRate.value,
            );

            return {
                iva: total.iva + lineSplit.iva,
                barber: total.barber + lineSplit.barber,
                shop: total.shop + lineSplit.shop,
            };
        },
        { iva: 0, barber: 0, shop: 0 },
    ),
);

const canPreview = computed(
    () =>
        lines.value.length > 0 &&
        !!selectedMethod.value &&
        !!selectedBarber.value,
);

function submit() {
    form.transform((data) => ({
        ...data,
        tip: data.tip || 0,
        performed_at: data.performed_at || undefined,
    })).submit(props.action, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (!props.transaction) {
                form.reset(
                    'services',
                    'payment_method_id',
                    'tip',
                    'notes',
                    'performed_at',
                );
            }
        },
    });
}

const chip =
    'rounded-lg border px-3 py-3 text-left text-sm transition-colors hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none';
const chipActive =
    'border-primary bg-primary text-primary-foreground hover:bg-primary/90';
</script>

<template>
    <form class="grid gap-6 lg:grid-cols-[1fr_320px]" @submit.prevent="submit">
        <div class="space-y-6">
            <section v-if="isAdmin" class="space-y-2">
                <Label>1. Barbero</Label>
                <div
                    class="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4"
                >
                    <button
                        v-for="barber in barbers"
                        :key="barber.id"
                        type="button"
                        :class="[
                            chip,
                            form.barber_id === barber.id && chipActive,
                        ]"
                        @click="form.barber_id = barber.id"
                    >
                        <span class="block font-medium">{{ barber.name }}</span>
                        <span class="text-xs opacity-75">
                            Comisión {{ formatPercent(barber.commission_rate) }}
                        </span>
                    </button>
                </div>
                <p
                    v-if="barbers.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No hay barberos activos. Créalos en Catálogos → Barberos.
                </p>
                <InputError :message="form.errors.barber_id" />
            </section>

            <section class="space-y-2">
                <Label>
                    {{ isAdmin ? '2.' : '1.' }} Servicios
                    <span class="font-normal text-muted-foreground"
                        >(puedes elegir varios)</span
                    >
                </Label>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    <button
                        v-for="service in services"
                        :key="service.id"
                        type="button"
                        :aria-pressed="isSelected(service.id)"
                        :class="[chip, isSelected(service.id) && chipActive]"
                        @click="toggleService(service)"
                    >
                        <span class="block font-medium">{{
                            service.name
                        }}</span>
                        <span class="text-xs tabular-nums opacity-75">
                            {{ formatMoney(service.base_price) }}
                        </span>
                    </button>
                </div>
                <InputError :message="servicesError" />
            </section>

            <section class="grid gap-4 sm:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label>{{
                        lines.length > 1
                            ? 'Monto de los servicios'
                            : 'Monto del servicio'
                    }}</Label>
                    <div
                        v-if="lines.length === 0"
                        class="flex h-9 items-center rounded-md border border-dashed px-3 text-sm text-muted-foreground"
                    >
                        Elige un servicio
                    </div>
                    <div
                        v-for="({ line, service }, index) in lines"
                        :key="service.id"
                        class="grid gap-1"
                    >
                        <div class="flex items-center gap-2">
                            <Label
                                :for="`amount-${service.id}`"
                                class="min-w-0 flex-1 truncate font-normal"
                            >
                                {{ service.name }}
                            </Label>
                            <Input
                                v-if="isAdmin"
                                :id="`amount-${service.id}`"
                                v-model.number="line.amount"
                                class="w-32"
                                type="number"
                                inputmode="numeric"
                                min="0"
                                step="100"
                            />
                            <div
                                v-else
                                class="flex h-9 w-32 items-center justify-end rounded-md border bg-muted/50 px-3 text-sm tabular-nums"
                            >
                                {{ formatMoney(service.base_price) }}
                            </div>
                        </div>
                        <p
                            v-if="
                                isAdmin &&
                                amountOf(line, service) !== service.base_price
                            "
                            class="text-xs text-amber-600 dark:text-amber-400"
                        >
                            Precio base:
                            {{ formatMoney(service.base_price) }}. Indica el
                            motivo en las notas.
                        </p>
                        <InputError :message="amountError(index)" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="tip">Propina (opcional)</Label>
                    <Input
                        id="tip"
                        v-model.number="form.tip"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        step="100"
                        placeholder="0"
                    />
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="preset in tipPresets"
                            :key="preset"
                            type="button"
                            class="rounded-md border px-2 py-1 text-xs tabular-nums transition-colors hover:bg-accent"
                            :class="{
                                'border-primary bg-primary/10':
                                    (form.tip ?? 0) === preset,
                            }"
                            @click="form.tip = preset"
                        >
                            {{
                                preset === 0
                                    ? 'Sin propina'
                                    : formatMoney(preset)
                            }}
                        </button>
                    </div>
                    <InputError :message="form.errors.tip" />
                </div>
            </section>

            <section class="space-y-2">
                <Label>{{ isAdmin ? '3.' : '2.' }} Método de pago</Label>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <button
                        v-for="method in paymentMethods"
                        :key="method.id"
                        type="button"
                        :class="[
                            chip,
                            'text-center',
                            form.payment_method_id === method.id && chipActive,
                        ]"
                        @click="form.payment_method_id = method.id"
                    >
                        <span class="block font-medium">{{ method.name }}</span>
                        <span class="text-xs opacity-75">
                            IVA {{ formatPercent(method.iva_rate) }}
                        </span>
                    </button>
                </div>
                <InputError :message="form.errors.payment_method_id" />
            </section>

            <section class="grid gap-4 sm:grid-cols-2">
                <div v-if="isAdmin" class="grid gap-2">
                    <Label for="performed_at">
                        Fecha y hora
                        <span
                            v-if="!transaction"
                            class="font-normal text-muted-foreground"
                            >(vacío = ahora)</span
                        >
                    </Label>
                    <Input
                        id="performed_at"
                        v-model="form.performed_at"
                        type="datetime-local"
                        :required="!!transaction"
                    />
                    <InputError :message="form.errors.performed_at" />
                </div>
                <div class="grid gap-2" :class="{ 'sm:col-span-2': !isAdmin }">
                    <Label for="notes">Notas (opcional)</Label>
                    <Input
                        id="notes"
                        v-model="form.notes"
                        maxlength="255"
                        placeholder="Ej: descuento cliente frecuente"
                    />
                    <InputError :message="form.errors.notes" />
                </div>
            </section>
            <InputError v-if="!isAdmin" :message="form.errors.performed_at" />
        </div>

        <aside class="lg:sticky lg:top-4 lg:self-start">
            <div class="space-y-4 rounded-xl border bg-card p-5 shadow-xs">
                <h3 class="font-semibold">Resumen</h3>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Barbero</dt>
                        <dd class="font-medium">
                            {{ selectedBarber?.name ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Pago</dt>
                        <dd class="font-medium">
                            {{ selectedMethod?.name ?? '—' }}
                        </dd>
                    </div>
                </dl>

                <dl class="space-y-2 border-t pt-4 text-sm tabular-nums">
                    <div
                        v-for="{ line, service } in lines"
                        :key="service.id"
                        class="flex justify-between gap-4"
                    >
                        <dt class="min-w-0 truncate">{{ service.name }}</dt>
                        <dd>{{ formatMoney(amountOf(line, service)) }}</dd>
                    </div>
                    <div v-if="lines.length === 0" class="flex justify-between">
                        <dt class="text-muted-foreground">Servicio</dt>
                        <dd>—</dd>
                    </div>
                    <div
                        v-if="lines.length > 1"
                        class="flex justify-between font-medium"
                    >
                        <dt>Subtotal ({{ lines.length }} servicios)</dt>
                        <dd>{{ formatMoney(amount) }}</dd>
                    </div>
                    <div class="flex justify-between text-muted-foreground">
                        <dt>IVA ({{ formatPercent(ivaRate) }})</dt>
                        <dd>− {{ formatMoney(canPreview ? split.iva : 0) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Barbero ({{ formatPercent(commissionRate) }})</dt>
                        <dd>
                            {{ formatMoney(canPreview ? split.barber : 0) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Barbería</dt>
                        <dd>{{ formatMoney(canPreview ? split.shop : 0) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Propina (100 % barbero)</dt>
                        <dd>{{ formatMoney(form.tip ?? 0) }}</dd>
                    </div>
                </dl>

                <div class="flex items-baseline justify-between border-t pt-4">
                    <span class="text-sm text-muted-foreground"
                        >Total a cobrar</span
                    >
                    <span class="text-2xl font-semibold tabular-nums">
                        {{ formatMoney(amount + Number(form.tip ?? 0)) }}
                    </span>
                </div>

                <Button
                    type="submit"
                    size="lg"
                    class="w-full"
                    :disabled="form.processing"
                    data-test="submit-transaction"
                >
                    <Spinner v-if="form.processing" />
                    {{ submitLabel }}
                </Button>
            </div>
        </aside>
    </form>
</template>
