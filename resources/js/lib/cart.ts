/**
 * The add-to-cart button turns green once the product is in the cart, so the
 * state is recognisable at a glance and not only from its label. Shared here so
 * the product cards and the quick-order list stay the same colour.
 *
 * Green matches the 'completed' order status, which is the other place in the
 * portal where green means "this is taken care of".
 */
export const inCartButtonClasses =
    'bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500';
