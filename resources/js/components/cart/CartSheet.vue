<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ShoppingCart } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetFooter } from '@/components/ui/sheet';
import { useCart, type CartItem } from '@/composables/useCart';
import CartSheetItem from './CartSheetItem.vue';

const props = defineProps<{
    open: boolean;
    cartItems?: CartItem[];
    cartTotal?: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { formatPrice } = useCart();

const itemCount = computed(() => props.cartItems?.length || 0);
const displayTotal = computed(() => props.cartTotal || '0.00');

const VAT_RATE = 0.09;

const vatAmount = computed(() => {
    const total = parseFloat(displayTotal.value);
    return total * VAT_RATE;
});

const totalInclVat = computed(() => {
    const total = parseFloat(displayTotal.value);
    return total * (1 + VAT_RATE);
});

const handleOpenChange = (open: boolean) => {
    emit('update:open', open);
};

const goToFullCart = () => {
    emit('update:open', false);
    router.visit('/customer/cart');
};
</script>

<template>
    <Sheet :open="open" @update:open="handleOpenChange">
        <SheetContent side="right" class="w-full sm:max-w-md flex flex-col">
            <SheetHeader>
                <SheetTitle class="flex items-center gap-2">
                    <ShoppingCart class="h-5 w-5" />
                    Winkelwagen
                    <span v-if="itemCount > 0" class="text-muted-foreground text-sm font-normal">
                        ({{ itemCount }} {{ itemCount === 1 ? 'item' : 'items' }})
                    </span>
                </SheetTitle>
            </SheetHeader>

            <!-- Empty State -->
            <div
                v-if="!cartItems || cartItems.length === 0"
                class="flex-1 flex flex-col items-center justify-center text-center p-6"
            >
                <ShoppingCart class="h-16 w-16 text-muted-foreground/50 mb-4" />
                <h3 class="text-lg font-semibold mb-2">Je winkelwagen is leeg</h3>
                <p class="text-sm text-muted-foreground mb-4">
                    Voeg producten toe om een bestelling te plaatsen
                </p>
                <Button as-child>
                    <Link href="/customer/products" @click="emit('update:open', false)">
                        Bekijk producten
                    </Link>
                </Button>
            </div>

            <!-- Cart Items -->
            <div v-else class="flex-1 flex flex-col gap-3 overflow-y-auto py-4">
                <CartSheetItem
                    v-for="item in cartItems"
                    :key="item.id"
                    :item="item"
                />
            </div>

            <!-- Footer with Total and Actions -->
            <SheetFooter v-if="cartItems && cartItems.length > 0" class="flex-col gap-3 pt-4 border-t">
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-muted-foreground">Subtotaal (ex. BTW):</span>
                        <span class="font-medium">{{ formatPrice(displayTotal) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-muted-foreground">BTW (9%):</span>
                        <span class="font-medium">{{ formatPrice(vatAmount.toFixed(2)) }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t">
                        <span class="text-base font-medium">Totaal (incl. BTW):</span>
                        <span class="text-lg font-bold">{{ formatPrice(totalInclVat.toFixed(2)) }}</span>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <Button variant="outline" @click="goToFullCart" class="w-full">
                        Bekijk volledige winkelwagen
                    </Button>
                    <Button as-child class="w-full">
                        <Link href="/customer/cart" @click="emit('update:open', false)">
                            Bestelling plaatsen
                        </Link>
                    </Button>
                </div>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
