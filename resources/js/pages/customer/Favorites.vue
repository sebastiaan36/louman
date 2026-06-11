<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Zap, ShoppingCart, X, Minus, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCart } from '@/composables/useCart';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatEuro as formatPrice } from '@/lib/price';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface Product {
    id: number;
    title: string;
    price: string;
    weight: string | null;
    thumbnail_url: string | null;
    is_in_stock: boolean;
    is_favorite: boolean;
    in_cart: boolean;
}

const props = defineProps<{
    favorites: Product[];
}>();

const { addToCart } = useCart();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Quick Order',
    },
];

// Track quantities for each product
const quantities = ref<Record<number, number>>(
    props.favorites.reduce((acc, product) => {
        acc[product.id] = 1;
        return acc;
    }, {} as Record<number, number>)
);

const viewProduct = (productId: number) => {
    router.visit(`/customer/products/${productId}`);
};

const handleFavoriteToggle = (productId: number) => {
    router.post(`/customer/favorites/${productId}/toggle`, {}, {
        preserveScroll: true,
    });
};

const handleAddToCart = (productId: number) => {
    const quantity = quantities.value[productId] || 1;
    addToCart(productId, quantity, {
        onSuccess: () => {
            // Reset quantity to 1 after adding
            quantities.value[productId] = 1;
        },
    });
};

const incrementQuantity = (productId: number) => {
    quantities.value[productId] = (quantities.value[productId] || 1) + 1;
};

const decrementQuantity = (productId: number) => {
    if (quantities.value[productId] > 1) {
        quantities.value[productId]--;
    }
};

</script>

<template>
    <Head title="Quick Order" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <Zap class="h-6 w-6" />
                    Quick Order
                </h1>
                <p class="text-sm text-muted-foreground">
                    Je opgeslagen producten voor snelle herbestelling
                </p>
            </div>

            <!-- Empty State -->
            <div v-if="favorites.length === 0" class="rounded-lg border border-dashed p-12 text-center space-y-4">
                <Zap class="h-16 w-16 mx-auto text-muted-foreground" />
                <div>
                    <p class="text-lg font-medium text-muted-foreground">Geen quick order producten</p>
                    <p class="text-sm text-muted-foreground mt-2">
                        Je hebt nog geen producten toegevoegd aan quick order
                    </p>
                </div>
                <Link href="/customer/products">
                    <Button>
                        Blader door producten
                    </Button>
                </Link>
            </div>

            <!-- Favorites Table -->
            <div v-else class="space-y-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">
                        {{ favorites.length }} {{ favorites.length === 1 ? 'product' : 'producten' }}
                    </p>
                </div>

                <div class="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-20">Foto</TableHead>
                                <TableHead>Product</TableHead>
                                <TableHead>Gewicht</TableHead>
                                <TableHead class="text-right">Prijs</TableHead>
                                <TableHead class="text-center w-40">Aantal</TableHead>
                                <TableHead class="w-32"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="product in favorites" :key="product.id">
                                <!-- Thumbnail -->
                                <TableCell class="cursor-pointer" @click="viewProduct(product.id)">
                                    <div class="w-16 h-16 rounded overflow-hidden">
                                        <img
                                            v-if="product.thumbnail_url"
                                            :src="product.thumbnail_url"
                                            :alt="product.title"
                                            class="w-full h-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full bg-muted flex items-center justify-center text-xs text-muted-foreground"
                                        >
                                            Geen foto
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Product Title -->
                                <TableCell class="font-medium cursor-pointer hover:underline" @click="viewProduct(product.id)">
                                    {{ product.title }}
                                </TableCell>

                                <!-- Weight -->
                                <TableCell>
                                    <span v-if="product.weight" class="text-sm text-muted-foreground">
                                        circa {{ product.weight }}
                                    </span>
                                    <span v-else class="text-sm text-muted-foreground">-</span>
                                </TableCell>

                                <!-- Price -->
                                <TableCell class="text-right font-semibold">
                                    {{ formatPrice(product.price) }}
                                </TableCell>

                                <!-- Quantity Input -->
                                <TableCell>
                                    <div class="flex items-center justify-center gap-2">
                                        <Button
                                            size="icon"
                                            variant="outline"
                                            class="h-8 w-8"
                                            @click="decrementQuantity(product.id)"
                                            :disabled="!product.is_in_stock || quantities[product.id] <= 1"
                                            :title="!product.is_in_stock ? 'Niet op voorraad' : undefined"
                                        >
                                            <Minus class="h-4 w-4" />
                                        </Button>
                                        <Input
                                            v-model.number="quantities[product.id]"
                                            type="number"
                                            min="1"
                                            class="w-16 text-center"
                                            :disabled="!product.is_in_stock"
                                            :title="!product.is_in_stock ? 'Niet op voorraad' : undefined"
                                        />
                                        <Button
                                            size="icon"
                                            variant="outline"
                                            class="h-8 w-8"
                                            @click="incrementQuantity(product.id)"
                                            :disabled="!product.is_in_stock"
                                            :title="!product.is_in_stock ? 'Niet op voorraad' : undefined"
                                        >
                                            <Plus class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>

                                <!-- Actions -->
                                <TableCell>
                                    <div class="flex items-center gap-2 justify-end">
                                        <Button
                                            size="sm"
                                            :disabled="!product.is_in_stock"
                                            :title="!product.is_in_stock ? 'Niet op voorraad' : undefined"
                                            @click="handleAddToCart(product.id)"
                                        >
                                            <ShoppingCart class="h-4 w-4 mr-2" />
                                            {{ product.in_cart ? 'Toegevoegd' : 'Toevoegen' }}
                                        </Button>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="h-8 w-8"
                                            @click="handleFavoriteToggle(product.id)"
                                        >
                                            <X class="h-4 w-4 text-muted-foreground" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
