/**
 * Shared colour and label mapping for order statuses, so every screen shows
 * the same coloured badge per status.
 */
export function orderStatusClasses(status: string): string {
    const classes: Record<string, string> = {
        pending: 'bg-yellow-500 text-white',
        confirmed: 'bg-blue-500 text-white',
        completed: 'bg-green-500 text-white',
        cancelled: 'bg-red-500 text-white',
    };

    return classes[status] ?? 'bg-gray-500 text-white';
}

export function orderStatusLabel(status: string): string {
    const labels: Record<string, string> = {
        pending: 'In behandeling',
        confirmed: 'Bevestigd',
        completed: 'Voltooid',
        cancelled: 'Geannuleerd',
    };

    return labels[status] ?? status;
}
