<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ShoppingCart } from 'lucide-vue-next';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import CartSheet from '@/components/cart/CartSheet.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useCart, type CartItem } from '@/composables/useCart';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const { cartCount, isCustomer } = useCart();

const cartSheetOpen = ref(false);
const cartItems = ref<CartItem[]>([]);
const cartTotal = ref('0.00');
const cartDataLoaded = ref(false);

// Load cart data when sheet opens for the first time
watch(cartSheetOpen, (isOpen) => {
    if (isOpen && !cartDataLoaded.value) {
        router.reload({
            only: ['cartItems', 'cartTotal'],
            onSuccess: (page) => {
                cartItems.value = (page.props.cartItems as CartItem[]) || [];
                cartTotal.value = (page.props.cartTotal as string) || '0.00';
                cartDataLoaded.value = true;
            },
        });
    }
});

// Update cart data when already loaded and sheet opens
watch(cartSheetOpen, (isOpen) => {
    if (isOpen && cartDataLoaded.value) {
        cartItems.value = (page.props.cartItems as CartItem[]) || [];
        cartTotal.value = (page.props.cartTotal as string) || '0.00';
    }
});

// Watch for changes in cart data from Inertia props (when items are removed while sheet is open)
watch(() => page.props.cartItems, (newCartItems) => {
    if (cartDataLoaded.value) {
        cartItems.value = (newCartItems as CartItem[]) || [];
    }
}, { deep: true });

watch(() => page.props.cartTotal, (newCartTotal) => {
    if (cartDataLoaded.value) {
        cartTotal.value = (newCartTotal as string) || '0.00';
    }
});

// Listen for cart item added event and open cart sheet
const handleCartItemAdded = () => {
    cartSheetOpen.value = true;
};

onMounted(() => {
    window.addEventListener('cart:item-added', handleCartItemAdded);
});

onUnmounted(() => {
    window.removeEventListener('cart:item-added', handleCartItemAdded);
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div v-if="isCustomer" class="flex items-center gap-2">
            <Button
                variant="ghost"
                size="icon"
                class="relative"
                @click="cartSheetOpen = true"
            >
                <ShoppingCart class="h-5 w-5" />
                <Badge
                    v-if="cartCount > 0"
                    class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 text-xs"
                >
                    {{ cartCount }}
                </Badge>
            </Button>
        </div>
    </header>

    <CartSheet
        v-model:open="cartSheetOpen"
        :cart-items="cartItems"
        :cart-total="cartTotal"
    />
</template>
