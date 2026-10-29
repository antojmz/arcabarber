<script setup lang="ts">
import { Head, Link, useForm, usePoll } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';
import { Spinner } from '@/components/ui/spinner';
import { formatTime } from '@/lib/dates';
import { formatMoney } from '@/lib/money';
import { login } from '@/routes';
import { store } from '@/routes/public/services';

type Option = { id: number; name: string };

const props = defineProps<{
    barbers: Option[];
    services: (Option & { base_price: number })[];
    paymentMethods: Option[];
    today: {
        services_count: number;
        by_barber: { barber_id: number; services_count: number }[];
        transactions: {
            id: number;
            performed_at: string;
            barber_name: string;
            service_name: string;
            payment_method_name: string;
        }[];
    };
}>();

usePoll(30000, { only: ['today'] });

const todayLabel = new Intl.DateTimeFormat('es-CL', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
}).format(new Date());

const form = useForm({
    barber_id: null as number | null,
    service_ids: [] as number[],
    payment_method_id: null as number | null,
    tip: undefined as number | undefined,
    password: '',
});

const tipPresets = [0, 1000, 2000, 3000, 5000];

const servicesError = computed(() => {
    const errors = form.errors as Record<string, string | undefined>;

    return (
        errors.service_ids ??
        Object.entries(errors).find(([field]) =>
            field.startsWith('service_ids.'),
        )?.[1]
    );
});

// Raised by the backend when today's period was already closed.
const periodError = computed(
    () => (form.errors as Record<string, string | undefined>).performed_at,
);

const selectedBarber = computed(() =>
    props.barbers.find((barber) => barber.id === form.barber_id),
);
const selectedServices = computed(() =>
    props.services.filter((service) => form.service_ids.includes(service.id)),
);
const servicesTotal = computed(() =>
    selectedServices.value.reduce(
        (total, service) => total + service.base_price,
        0,
    ),
);
const selectedMethod = computed(() =>
    props.paymentMethods.find((method) => method.id === form.payment_method_id),
);

// The password modal can only be closed by its own buttons.
const confirmOpen = ref(false);

function servicesOf(barberId: number): number {
    return (
        props.today.by_barber.find((row) => row.barber_id === barberId)
            ?.services_count ?? 0
    );
}

function toggleService(serviceId: number) {
    form.service_ids = form.service_ids.includes(serviceId)
        ? form.service_ids.filter((id) => id !== serviceId)
        : [...form.service_ids, serviceId];
}

function openConfirmation() {
    form.clearErrors();

    if (!selectedBarber.value) {
        form.setError('barber_id', 'Selecciona un barbero.');
    }

    if (selectedServices.value.length === 0) {
        form.setError('service_ids', 'Selecciona al menos un servicio.');
    }

    if (!selectedMethod.value) {
        form.setError('payment_method_id', 'Selecciona un método de pago.');
    }

    const tip = form.tip;

    if (tip !== undefined && (!Number.isInteger(tip) || tip < 0)) {
        form.setError('tip', 'La propina debe ser un monto entero positivo.');
    }

    if (!form.hasErrors) {
        confirmOpen.value = true;
    }
}

/**
 * Close the modal keeping the selections, so they can be corrected and confirmed again.
 */
function cancelConfirmation() {
    form.reset('password');
    form.clearErrors();
    confirmOpen.value = false;
}

/**
 * Close the modal after a successful registration, leaving the form ready for the next service.
 */
function finishRegistration() {
    form.reset();
    form.clearErrors();
    confirmOpen.value = false;
}

function register() {
    form.transform((data) => ({ ...data, tip: data.tip || 0 })).submit(
        store(),
        {
            preserveScroll: true,
            onSuccess: () => finishRegistration(),
            onError: (errors) => {
                form.reset('password');

                // Errors outside the modal are shown on the form, so it closes.
                if (Object.keys(errors).some((field) => field !== 'password')) {
                    confirmOpen.value = false;
                }
            },
        },
    );
}

const chip =
    'rounded-lg border bg-card px-3 py-3 text-left text-sm transition-colors hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none';
const chipActive =
    'border-primary bg-primary text-primary-foreground hover:bg-primary/90';
</script>

<template>
    <Head title="Actividad de hoy" />

    <div
        class="relative min-h-svh overflow-hidden bg-background text-foreground"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 top-0 h-96 bg-[radial-gradient(ellipse_at_top,var(--color-gold)_0%,transparent_65%)] opacity-20 dark:opacity-10"
        />

        <div class="relative mx-auto max-w-6xl space-y-6 p-4 md:p-8">
            <header class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <AppLogoIcon
                        class="size-12 shadow-sm ring-1 ring-gold/40"
                    />
                    <div>
                        <h1
                            class="font-display text-2xl font-semibold tracking-wide"
                        >
                            Actividad de hoy
                        </h1>
                        <p
                            class="text-sm text-muted-foreground first-letter:uppercase"
                        >
                            {{ todayLabel }}
                        </p>
                    </div>
                </div>
                <div
                    class="rounded-xl border border-gold/50 bg-gradient-to-br from-gold/15 to-primary/10 px-5 py-3 text-right"
                >
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Servicios del día
                    </p>
                    <p class="text-3xl font-semibold text-primary tabular-nums">
                        {{ today.services_count }}
                    </p>
                </div>
            </header>

            <section
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"
            >
                <div
                    v-for="barber in barbers"
                    :key="barber.id"
                    class="rounded-xl border bg-card p-4 shadow-xs"
                >
                    <p class="truncate font-medium">{{ barber.name }}</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums">
                        {{ servicesOf(barber.id) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            servicesOf(barber.id) === 1
                                ? 'servicio'
                                : 'servicios'
                        }}
                    </p>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[3fr_2fr]">
                <form
                    class="space-y-6 rounded-xl border bg-card p-5 shadow-sm"
                    @submit.prevent="openConfirmation"
                >
                    <h2
                        class="font-display text-xl font-semibold tracking-wide"
                    >
                        Registrar servicio
                    </h2>

                    <section class="space-y-2">
                        <Label>1. Barbero</Label>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
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
                                <span class="block font-medium">{{
                                    barber.name
                                }}</span>
                            </button>
                        </div>
                        <InputError :message="form.errors.barber_id" />
                    </section>

                    <section class="space-y-2">
                        <Label>
                            2. Servicios
                            <span class="font-normal text-muted-foreground"
                                >(puedes elegir varios)</span
                            >
                        </Label>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <button
                                v-for="service in services"
                                :key="service.id"
                                type="button"
                                :aria-pressed="
                                    form.service_ids.includes(service.id)
                                "
                                :class="[
                                    chip,
                                    form.service_ids.includes(service.id) &&
                                        chipActive,
                                ]"
                                @click="toggleService(service.id)"
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

                    <section class="space-y-2">
                        <Label>3. Método de pago</Label>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <button
                                v-for="method in paymentMethods"
                                :key="method.id"
                                type="button"
                                :class="[
                                    chip,
                                    'text-center',
                                    form.payment_method_id === method.id &&
                                        chipActive,
                                ]"
                                @click="form.payment_method_id = method.id"
                            >
                                <span class="font-medium">{{
                                    method.name
                                }}</span>
                            </button>
                        </div>
                        <InputError :message="form.errors.payment_method_id" />
                    </section>

                    <section>
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

                    <InputError :message="periodError" />

                    <div
                        class="flex items-baseline justify-between rounded-lg border bg-muted/40 px-4 py-3"
                        data-test="public-total"
                    >
                        <span class="text-sm text-muted-foreground">
                            Total a cobrar
                            <template v-if="selectedServices.length > 0">
                                ({{ selectedServices.length }}
                                {{
                                    selectedServices.length === 1
                                        ? 'servicio'
                                        : 'servicios'
                                }})
                            </template>
                        </span>
                        <span class="text-2xl font-semibold tabular-nums">
                            {{
                                formatMoney(
                                    servicesTotal + Number(form.tip ?? 0),
                                )
                            }}
                        </span>
                    </div>

                    <Button
                        type="submit"
                        size="lg"
                        class="w-full"
                        :disabled="form.processing"
                        data-test="public-continue"
                    >
                        Continuar
                    </Button>
                </form>

                <!-- On large screens the list takes the form's height and scrolls inside. -->
                <section class="flex flex-col gap-3">
                    <h2
                        class="font-display text-xl font-semibold tracking-wide"
                    >
                        Servicios de hoy
                    </h2>
                    <ul
                        class="max-h-[28rem] divide-y overflow-y-auto rounded-xl border bg-card lg:h-0 lg:max-h-none lg:grow lg:basis-0"
                    >
                        <li
                            v-for="transaction in today.transactions"
                            :key="transaction.id"
                            class="flex items-center justify-between gap-3 px-4 py-3 text-sm"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {{ transaction.service_name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ transaction.barber_name }} ·
                                    {{ transaction.payment_method_name }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 text-muted-foreground tabular-nums"
                            >
                                {{ formatTime(transaction.performed_at) }}
                            </span>
                        </li>
                        <li
                            v-if="today.transactions.length === 0"
                            class="px-4 py-8 text-center text-sm text-muted-foreground"
                        >
                            Aún no hay servicios registrados hoy.
                        </li>
                    </ul>
                </section>
            </div>

            <footer class="text-center">
                <Link
                    :href="login()"
                    class="text-xs text-muted-foreground underline-offset-4 hover:underline"
                >
                    Ingreso personal
                </Link>
            </footer>
        </div>

        <Dialog :open="confirmOpen">
            <DialogContent
                :show-close-button="false"
                @interact-outside="(event: Event) => event.preventDefault()"
                @escape-key-down="(event: Event) => event.preventDefault()"
            >
                <form class="space-y-6" @submit.prevent="register">
                    <DialogHeader class="space-y-3">
                        <DialogTitle>Confirmar registro</DialogTitle>
                        <DialogDescription>
                            {{ selectedBarber?.name }} confirma
                            {{
                                selectedServices.length > 1
                                    ? 'los servicios'
                                    : 'el servicio'
                            }}
                            con su contraseña.
                        </DialogDescription>
                    </DialogHeader>

                    <dl
                        class="space-y-2 rounded-lg border bg-muted/40 p-4 text-sm"
                    >
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Barbero</dt>
                            <dd class="font-medium">
                                {{ selectedBarber?.name }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">
                                {{
                                    selectedServices.length > 1
                                        ? 'Servicios'
                                        : 'Servicio'
                                }}
                            </dt>
                            <dd class="space-y-1 text-right font-medium">
                                <p
                                    v-for="service in selectedServices"
                                    :key="service.id"
                                >
                                    {{ service.name }}
                                    <span
                                        class="text-muted-foreground tabular-nums"
                                    >
                                        · {{ formatMoney(service.base_price) }}
                                    </span>
                                </p>
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">
                                Método de pago
                            </dt>
                            <dd class="font-medium">
                                {{ selectedMethod?.name }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Propina</dt>
                            <dd class="font-medium tabular-nums">
                                {{
                                    form.tip
                                        ? formatMoney(form.tip)
                                        : 'Sin propina'
                                }}
                            </dd>
                        </div>
                        <div
                            class="flex items-baseline justify-between gap-4 border-t pt-2"
                        >
                            <dt class="text-muted-foreground">
                                Total a cobrar
                            </dt>
                            <dd class="text-lg font-semibold tabular-nums">
                                {{
                                    formatMoney(
                                        servicesTotal + Number(form.tip ?? 0),
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>

                    <div class="grid gap-2">
                        <Label for="password">
                            Contraseña de {{ selectedBarber?.name }}
                        </Label>
                        <PasswordInput
                            id="password"
                            v-model="form.password"
                            autocomplete="off"
                            inputmode="numeric"
                            autofocus
                            required
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            :disabled="form.processing"
                            data-test="public-cancel"
                            @click="cancelConfirmation"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing || !form.password"
                            data-test="public-register-service"
                        >
                            <Spinner v-if="form.processing" />
                            Registrar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Toaster />
    </div>
</template>
