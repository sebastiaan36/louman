import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface CartItem {
    id: number;
    product_id: number;
    product_title: string;
    product_price: string;
    product_weight: string | null;
    product_thumbnail: string | null;
    quantity: number;
    subtotal: number;
    is_available: boolean;
}

export function useCart() {
    const page = usePage();

    const cartCount = computed(() => page.props.cartCount as number || 0);
    const isCustomer = computed(() => {
        const user = page.props.auth?.user as { role?: string } | undefined;
        return user?.role === 'customer';
    });

    /**
     * Format price to Dutch currency format
     */
    const formatPrice = (price: string | number): string => {
        return new Intl.NumberFormat('nl-NL', {
            style: 'currency',
            currency: 'EUR',
        }).format(typeof price === 'string' ? parseFloat(price) : price);
    };

    /**
     * Update cart item quantity
     */
    const updateQuantity = (
        cartItemId: number,
        newQuantity: number,
        options?: {
            onSuccess?: () => void;
            onError?: () => void;
        }
    ) => {
        router.patch(
            `/customer/cart/${cartItemId}`,
            { quantity: newQuantity },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['cartCount', 'cartItems', 'cartTotal'] });
                    options?.onSuccess?.();
                },
                onError: options?.onError,
            }
        );
    };

    /**
     * Increment cart item quantity by 1
     */
    const incrementQuantity = (
        cartItemId: number,
        currentQuantity: number,
        options?: {
            onSuccess?: () => void;
            onError?: () => void;
        }
    ) => {
        updateQuantity(cartItemId, currentQuantity + 1, options);
    };

    /**
     * Decrement cart item quantity by 1 (minimum 1)
     */
    const decrementQuantity = (
        cartItemId: number,
        currentQuantity: number,
        options?: {
            onSuccess?: () => void;
            onError?: () => void;
        }
    ) => {
        if (currentQuantity > 1) {
            updateQuantity(cartItemId, currentQuantity - 1, options);
        }
    };

    /**
     * Remove item from cart
     */
    const removeItem = (
        cartItemId: number,
        options?: {
            confirm?: boolean;
            onSuccess?: () => void;
            onError?: () => void;
        }
    ) => {
        const shouldRemove = options?.confirm !== false
            ? confirm('Weet je zeker dat je dit product wilt verwijderen?')
            : true;

        if (shouldRemove) {
            router.delete(`/customer/cart/${cartItemId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['cartCount', 'cartItems', 'cartTotal'] });
                    options?.onSuccess?.();
                },
                onError: options?.onError,
            });
        }
    };

    /**
     * Clear entire cart
     */
    const clearCart = (options?: {
        confirm?: boolean;
        onSuccess?: () => void;
        onError?: () => void;
    }) => {
        const shouldClear = options?.confirm !== false
            ? confirm('Weet je zeker dat je de hele winkelwagen wilt legen?')
            : true;

        if (shouldClear) {
            router.delete('/customer/cart', {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['cartCount', 'cartItems', 'cartTotal'] });
                    options?.onSuccess?.();
                },
                onError: options?.onError,
            });
        }
    };

    /**
     * Add product to cart
     */
    const addToCart = (
        productId: number,
        quantity: number = 1,
        options?: {
            onSuccess?: () => void;
            onError?: () => void;
        }
    ) => {
        router.post(
            `/customer/cart/${productId}/add`,
            { quantity },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['cartCount'] });
                    // Dispatch event to open cart sheet
                    window.dispatchEvent(new CustomEvent('cart:item-added'));
                    options?.onSuccess?.();
                },
                onError: options?.onError,
            }
        );
    };

    return {
        cartCount,
        isCustomer,
        formatPrice,
        updateQuantity,
        incrementQuantity,
        decrementQuantity,
        removeItem,
        clearCart,
        addToCart,
    };
}
