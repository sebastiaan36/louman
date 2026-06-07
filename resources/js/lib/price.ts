/**
 * Format a price value in Dutch notation (comma as decimal separator),
 * e.g. 5.95 -> "5,95" and 1234.5 -> "1.234,50". The euro sign is left to
 * the caller so it can be placed/styled as needed.
 */
export function formatPrice(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value;

    return new Intl.NumberFormat('nl-NL', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(num) ? num : 0);
}

/**
 * Format a price as a Dutch euro amount, e.g. "€ 5,95".
 */
export function formatEuro(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value;

    return new Intl.NumberFormat('nl-NL', {
        style: 'currency',
        currency: 'EUR',
    }).format(Number.isFinite(num) ? num : 0);
}
