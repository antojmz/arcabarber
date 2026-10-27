import { usePage } from '@inertiajs/vue3';
import {
    CreditCard,
    FileBarChart,
    History,
    LayoutGrid,
    ListChecks,
    PlusCircle,
    Scissors,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';
import { panel } from '@/routes';
import barbers from '@/routes/admin/barbers';
import closings from '@/routes/admin/closings';
import paymentMethods from '@/routes/admin/payment-methods';
import reports from '@/routes/admin/reports';
import services from '@/routes/admin/services';
import { production } from '@/routes/my';
import transactions from '@/routes/transactions';
import type { NavItem } from '@/types';

export type NavGroup = {
    label: string;
    items: NavItem[];
};

export function useNavigation(): ComputedRef<NavGroup[]> {
    const page = usePage();

    return computed(() => {
        if (page.props.auth.user?.role !== 'admin') {
            return [
                {
                    label: 'Mi trabajo',
                    items: [
                        {
                            title: 'Mi panel',
                            href: panel(),
                            icon: LayoutGrid,
                        },
                        {
                            title: 'Registrar servicio',
                            href: transactions.create(),
                            icon: PlusCircle,
                        },
                        {
                            title: 'Mi historial',
                            href: transactions.index(),
                            icon: ListChecks,
                        },
                        {
                            title: 'Mi producción',
                            href: production(),
                            icon: Wallet,
                        },
                    ],
                },
            ];
        }

        return [
            {
                label: 'Operación',
                items: [
                    { title: 'Panel', href: panel(), icon: LayoutGrid },
                    {
                        title: 'Registrar servicio',
                        href: transactions.create(),
                        icon: PlusCircle,
                    },
                    {
                        title: 'Transacciones',
                        href: transactions.index(),
                        icon: ListChecks,
                    },
                ],
            },
            {
                label: 'Cierres',
                items: [
                    {
                        title: 'Liquidación por barbero',
                        href: reports.barber(),
                        icon: Wallet,
                    },
                    {
                        title: 'Cierre general',
                        href: reports.general(),
                        icon: FileBarChart,
                    },
                    {
                        title: 'Historial de cierres',
                        href: closings.index(),
                        icon: History,
                    },
                ],
            },
            {
                label: 'Catálogos',
                items: [
                    { title: 'Barberos', href: barbers.index(), icon: Users },
                    {
                        title: 'Servicios',
                        href: services.index(),
                        icon: Scissors,
                    },
                    {
                        title: 'Métodos de pago',
                        href: paymentMethods.index(),
                        icon: CreditCard,
                    },
                ],
            },
        ];
    });
}
