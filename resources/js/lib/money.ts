const clp = new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    maximumFractionDigits: 0,
});

export function formatMoney(value: number | string | null | undefined): string {
    return clp.format(Number(value ?? 0));
}

export function formatPercent(
    value: number | string | null | undefined,
): string {
    return `${Number(value ?? 0).toLocaleString('es-CL', { maximumFractionDigits: 2 })} %`;
}
