/**
 * The days a customer can be assigned to for delivery.
 *
 * Mirrors App\Support\DeliveryDay::ALL on the server. A test asserts the two
 * stay in step, so a day removed on one side cannot linger on the other —
 * which is how zaterdag and zondag survived in the customer screens after they
 * had already been dropped from the delivery route.
 */
export const DELIVERY_DAYS = [
    { value: 'maandag', label: 'Maandag' },
    { value: 'dinsdag', label: 'Dinsdag' },
    { value: 'woensdag', label: 'Woensdag' },
    { value: 'donderdag', label: 'Donderdag' },
    { value: 'vrijdag', label: 'Vrijdag' },
    { value: 'ophalen', label: 'Ophalen' },
] as const;

/**
 * Value of the extra "Niet bekend" choice in the admin day selects. It is not
 * a delivery day: choosing it sends null, so the day can be decided later.
 */
export const DELIVERY_DAY_UNKNOWN = 'onbekend';
export const DELIVERY_DAY_UNKNOWN_LABEL = 'Niet bekend';
