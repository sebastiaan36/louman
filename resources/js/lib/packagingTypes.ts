/**
 * How a customer's order is packed for delivery.
 *
 * Mirrors App\Support\PackagingType::ALL on the server; a test asserts the two
 * stay in step.
 */
export const PACKAGING_TYPES = [
    { value: 'tas', label: 'Tas' },
    { value: 'doos', label: 'Doos' },
    { value: 'krat', label: 'Krat' },
] as const;
