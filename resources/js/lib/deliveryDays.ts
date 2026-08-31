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
