/**
 * Convert a decimal amount (NPR) to integer paisa.
 * toPaisa(10.5) === 1050
 */
export function toPaisa(amount: number): number {
    return Math.round(amount * 100);
}

/**
 * Convert integer paisa back to decimal NPR, rounded to 2 decimal places.
 */
export function toMoney(paisa: number): number {
    return Math.round(paisa) / 100;
}

/**
 * Format an NPR decimal amount as a display string.
 */
export function formatNPR(amount: number): string {
    return `NPR ${amount.toFixed(2)}`;
}
