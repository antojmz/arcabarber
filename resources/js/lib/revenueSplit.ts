export type RevenueSplit = {
    atmFee: number;
    barber: number;
    shop: number;
};

/**
 * Mirror of App\Support\RevenueSplit, used only to preview the split while
 * filling the form. The backend always recalculates the final figures.
 */
export function calculateRevenueSplit(
    amount: number,
    atmFeeRate: number | string,
    barberCommissionRate: number | string,
): RevenueSplit {
    const atmFee = Math.round((amount * Number(atmFeeRate)) / 100);
    const net = amount - atmFee;
    const barber = Math.round((net * Number(barberCommissionRate)) / 100);

    return { atmFee, barber, shop: net - barber };
}
