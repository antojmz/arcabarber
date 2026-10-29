<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { formatMoney } from '@/lib/money';
import { joinServiceNames } from '@/lib/transactions';
import { voidMethod } from '@/routes/transactions';
import type { Transaction } from '@/types';

defineProps<{
    transaction: Transaction;
}>();

const open = ref(false);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                variant="ghost"
                size="sm"
                class="text-destructive hover:text-destructive"
            >
                Anular
            </Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="voidMethod.form(transaction.id)"
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>¿Anular esta transacción?</DialogTitle>
                    <DialogDescription>
                        {{ joinServiceNames(transaction.items) }} de
                        {{ transaction.barber_name }} por
                        {{ formatMoney(transaction.amount) }}. La transacción no
                        se borra: queda registrada como anulada y deja de sumar
                        en reportes y cierres.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label :for="`void-reason-${transaction.id}`">Motivo</Label>
                    <Input
                        :id="`void-reason-${transaction.id}`"
                        name="void_reason"
                        required
                        maxlength="255"
                        placeholder="Ej: registrada por error"
                    />
                    <InputError :message="errors.void_reason" />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary"
                            >Cancelar</Button
                        >
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                    >
                        Anular transacción
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
