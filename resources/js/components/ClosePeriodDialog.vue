<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime, formatRange } from '@/lib/dates';
import { show, store } from '@/routes/admin/closings';
import type { DateRange } from '@/types';

defineProps<{
    period: string;
    date: string;
    range: DateRange;
    barberId?: number | null;
    barberName?: string;
    closing: { id: number; created_at: string | null } | null;
}>();
</script>

<template>
    <div
        v-if="closing"
        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm dark:border-emerald-900 dark:bg-emerald-950/40"
    >
        <p
            class="flex items-center gap-2 text-emerald-800 dark:text-emerald-200"
        >
            <Lock class="size-4" />
            Periodo cerrado
            <template v-if="closing.created_at"
                >el {{ formatDateTime(closing.created_at) }}</template
            >. Las transacciones de estas fechas ya no se pueden modificar.
        </p>
        <Button variant="outline" size="sm" as-child>
            <Link :href="show(closing.id)">Ver cierre</Link>
        </Button>
    </div>

    <Dialog v-else>
        <DialogTrigger as-child>
            <Button><Lock /> Cerrar periodo</Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="period" :value="period" />
                <input type="hidden" name="date" :value="date" />
                <input
                    v-if="barberId"
                    type="hidden"
                    name="barber_id"
                    :value="barberId"
                />

                <DialogHeader class="space-y-3">
                    <DialogTitle>
                        Cerrar {{ formatRange(range.start, range.end) }}
                    </DialogTitle>
                    <DialogDescription>
                        Se guardará una copia de los totales
                        {{
                            barberName
                                ? `de ${barberName}`
                                : 'de toda la barbería'
                        }}
                        y las transacciones de este periodo quedarán bloqueadas
                        (no se podrán editar ni anular).
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="closing-notes">Notas (opcional)</Label>
                    <Input id="closing-notes" name="notes" maxlength="1000" />
                    <InputError :message="errors.notes" />
                    <InputError :message="errors.date" />
                    <InputError :message="errors.period" />
                    <InputError :message="errors.barber_id" />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary"
                            >Cancelar</Button
                        >
                    </DialogClose>
                    <Button type="submit" :disabled="processing"
                        >Confirmar cierre</Button
                    >
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
