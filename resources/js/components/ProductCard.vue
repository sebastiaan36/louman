<script setup lang="ts">
import { Zap, ShoppingCart, Eye } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { formatEuro } from '@/lib/price';

interface Product {
    id: number;
    title: string;
    price: string;
    price_per_kg?: string | null;
    weight: string | null;
    thumbnail_url: string | null;
    is_in_stock: boolean;
    is_favorite?: boolean;
    in_cart?: boolean;
}

const props = defineProps<{
    product: Product;
    showActions?: boolean;
}>();

const emit = defineEmits<{
    favoriteToggle: [];
    addToCart: [];
    viewDetails: [];
}>();

const formattedPrice = computed(() => formatEuro(props.product.price));
</script>

<template>
    <Card class="overflow-hidden hover:shadow-lg transition-shadow">
        <CardHeader class="p-0">
            <div
                class="relative aspect-square cursor-pointer group"
                @click="showActions ? emit('viewDetails') : undefined"
            >
                <img
                    v-if="product.thumbnail_url"
                    :src="product.thumbnail_url"
                    :alt="product.title"
                    class="w-full h-full object-cover group-hover:opacity-95 transition-opacity"
                />
                <div
                    v-else
                    class="w-full h-full bg-muted flex items-center justify-center text-muted-foreground"
                >
                    Geen foto
                </div>

                <!-- Quick Order icon -->
                <Button
                    v-if="showActions"
                    size="icon"
                    variant="secondary"
                    class="absolute top-2 right-2"
                    @click.stop="emit('favoriteToggle')"
                >
                    <Zap
                        :class="[
                            'h-4 w-4',
                            product.is_favorite ? 'fill-yellow-500 text-yellow-500' : '',
                        ]"
                    />
                </Button>

            </div>
        </CardHeader>

        <CardContent class="p-4 space-y-2">
            <h3 class="font-semibold line-clamp-2 min-h-[3rem]">
                {{ product.title }}
            </h3>

            <div class="flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-2xl font-bold text-primary">
                        {{ formattedPrice }}
                    </span>
                    <span class="text-[10px] text-muted-foreground">ex. BTW</span>
                    <span v-if="product.price_per_kg" class="mt-2 text-xs text-muted-foreground">
                        {{ formatEuro(product.price_per_kg) }} per kg
                    </span>
                </div>
                <span v-if="product.weight" class="text-sm text-muted-foreground">
                    circa {{ product.weight }}
                </span>
            </div>
        </CardContent>

        <CardFooter v-if="showActions" class="p-4 pt-0 gap-2">
            <Button
                variant="outline"
                class="flex-1 min-w-0"
                @click="emit('viewDetails')"
            >
                <Eye class="h-4 w-4 mr-2 shrink-0" />
                Details
            </Button>
            <Button
                class="flex-1 min-w-0"
                :disabled="!product.is_in_stock"
                :title="!product.is_in_stock ? 'Niet op voorraad' : undefined"
                @click="emit('addToCart')"
            >
                <ShoppingCart class="h-4 w-4 mr-2 shrink-0" />
                <span class="truncate">{{ product.in_cart ? 'Toegevoegd' : 'Toevoegen' }}</span>
            </Button>
        </CardFooter>

        <slot name="actions" />
    </Card>
</template>
