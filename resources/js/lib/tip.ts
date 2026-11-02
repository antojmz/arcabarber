/**
 * Suggested tip percentages, applied over the total of the services.
 */
export const tipPercentages = [10, 15, 20];

/**
 * Tip for a percentage of the services total, rounded to whole pesos (CLP has no decimals).
 */
export function calculateTip(
    servicesTotal: number,
    percentage: number,
): number {
    return Math.round((servicesTotal * percentage) / 100);
}
