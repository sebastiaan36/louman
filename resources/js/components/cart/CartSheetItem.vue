<script setup lang="ts">
import { Minus, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCart, type CartItem } from '@/composables/useCart';

const props = defineProps<{
    item: CartItem;
}>();

const { formatPrice, incrementQuantity, decrementQuantity, removeItem } = useCart();
const quantity = ref(props.item.quantity);

const handleIncrement = () => {
    incrementQuantity(props.item.id, quantity.value, {
        onSuccess: () => {
            quantity.value++;
        },
    });
};

const handleDecrement = () => {
    if (quantity.value > 1) {
        decrementQuantity(props.item.id, quantity.value, {
            onSuccess: () => {
                quantity.value--;
            },
        });
    }
};

const handleRemove = () => {
    removeItem(props.item.id, { confirm: true });
};
</script>

<template>
    <div class="flex gap-3 p-3 border rounded-lg bg-card">
        <!-- Product Image -->
        <div class="flex-shrink-0">
            <div class="w-16 h-16 bg-muted rounded-md overflow-hidden">
                <img
                    v-if="item.product_thumbnail"
                    :src="item.product_thumbnail"
                    :alt="item.product_title"
                    class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground">
                    <span class="text-xs">Geen foto</span>
                </div>
            </div>
        </div>

        <!-- Product Info -->
        <div class="flex-1 min-w-0">
            <div class="flex justify-between items-start gap-2 mb-1">
                <h4 class="font-medium text-sm leading-tight line-clamp-2">
                    {{ item.product_title }}
                </h4>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 flex-shrink-0 text-destructive hover:text-destructive"
                    @click="handleRemove"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>

            <div class="flex items-center gap-2 text-xs text-muted-foreground mb-2">
                <span v-if="item.product_weight">{{ item.product_weight }}</span>
                <span>{{ formatPrice(item.product_price) }}</span>
            </div>

            <Badge v-if="!item.is_available" variant="destructive" class="mb-2">
                Niet beschikbaar
            </Badge>

            <!-- Quantity Controls and Subtotal -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        class="h-7 w-7"
                        :disabled="quantity <= 1"
                        @click="handleDecrement"
                    >
                        <Minus class="h-3 w-3" />
                    </Button>
                    <span class="w-8 text-center text-sm font-medium">{{ quantity }}</span>
                    <Button
                        variant="outline"
                        size="icon"
                        class="h-7 w-7"
                        @click="handleIncrement"
                    >
                        <Plus class="h-3 w-3" />
                    </Button>
                </div>
                <div class="text-sm font-semibold">
                    {{ formatPrice(item.subtotal) }}
                </div>
            </div>
        </div>
    </div>
</template>
