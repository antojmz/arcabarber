import type { TransactionItem } from '@/types';

/**
 * Name of a visit with several services, e.g. "Corte + Barba + Cejas".
 */
export function joinServiceNames(
    items: Pick<TransactionItem, 'service_name'>[],
): string {
    return items.map((item) => item.service_name).join(' + ');
}
