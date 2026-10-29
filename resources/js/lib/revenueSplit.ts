export type RevenueSplit = {
    iva: number;
    barber: number;
    shop: number;
};

/**
 * Mirror of App\Support\RevenueSplit, used only to preview the split while
 * filling the form. The backend always recalculates the final figures.
 */
export function calculateRevenueSplit(
    amount: number,
    ivaRate: number | string,
    barberCommissionRate: number | string,
): RevenueSplit {
    const iva = Math.round((amount * Number(ivaRate)) / 100);
    const net = amount - iva;
    const barber = Math.round((net * Number(barberCommissionRate)) / 100);

    return { iva, barber, shop: net - barber };
}
