<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search, Clock } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import ProductCard from '@/components/ProductCard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCart } from '@/composables/useCart';
import AppLayout from '@/layouts/AppLayout.vue';
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

interface Category {
    id: number;
    name: string;
}

interface Filters {
    category: string | null;
    search: string | null;
}

interface OrderDeadline {
    deadline: string;
    days: number;
    hours: number;
    minutes: number;
    total_hours: number;
    is_urgent: boolean;
}

const props = defineProps<{
    products: Product[];
    categories: Category[];
    filters: Filters;
    orderDeadline: OrderDeadline;
}>();

const { addToCart } = useCart();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Producten',
    },
];

const selectedCategory = ref(props.filters.category?.toString() || '');
const searchQuery = ref(props.filters.search || '');

// Watch for filter changes and update URL
watch([selectedCategory, searchQuery], ([category, search]) => {
    const params = new URLSearchParams();
    if (category) params.set('category', category);
    if (search) params.set('search', search);

    router.get(`/customer/products?${params.toString()}`, {}, {
        preserveState: true,
        preserveScroll: true,
    });
}, { deep: true });

const handleFavoriteToggle = (productId: number) => {
    router.post(`/customer/favorites/${productId}/toggle`, {}, {
        preserveScroll: true,
    });
};

const handleViewDetails = (productId: number) => {
    router.visit(`/customer/products/${productId}`);
};

const handleAddToCart = (productId: number) => {
    addToCart(productId, 1);
};

const clearFilters = () => {
    selectedCategory.value = '';
    searchQuery.value = '';
};
</script>

<template>
    <Head title="Producten" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-bold">Producten</h1>
                <p class="text-sm text-muted-foreground">
                    Blader door onze productcatalogus
                </p>
            </div>

            <!-- Order Deadline -->
            <Card :class="{ 'border-orange-500': orderDeadline.is_urgent }">
                <CardContent>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <Clock class="h-4 w-4" :class="{ 'text-orange-500': orderDeadline.is_urgent }" />
                            <span class="text-sm font-medium">Besteldeadline:</span>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Bestel voor maandag 12:00 uur voor levering
                        </p>
                        <div class="flex gap-2 ml-auto">
                            <div class="text-center px-3 py-1.5 rounded-lg bg-muted">
                                <div class="text-lg font-bold" :class="{ 'text-orange-500': orderDeadline.is_urgent }">
                                    {{ orderDeadline.days }}
                                </div>
                                <div class="text-[10px] text-muted-foreground">
                                    {{ orderDeadline.days === 1 ? 'dag' : 'dagen' }}
                                </div>
                            </div>
                            <div class="text-center px-3 py-1.5 rounded-lg bg-muted">
                                <div class="text-lg font-bold" :class="{ 'text-orange-500': orderDeadline.is_urgent }">
                                    {{ orderDeadline.hours }}
                                </div>
                                <div class="text-[10px] text-muted-foreground">
                                    {{ orderDeadline.hours === 1 ? 'uur' : 'uren' }}
                                </div>
                            </div>
                            <div class="text-center px-3 py-1.5 rounded-lg bg-muted">
                                <div class="text-lg font-bold" :class="{ 'text-orange-500': orderDeadline.is_urgent }">
                                    {{ orderDeadline.minutes }}
                                </div>
                                <div class="text-[10px] text-muted-foreground">
                                    min
                                </div>
                            </div>
                        </div>
                        <div v-if="orderDeadline.is_urgent" class="text-xs text-orange-600 font-medium">
                            ⚠️ Minder dan 24 uur!
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Filters -->
            <div class="rounded-lg border p-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="search">Zoeken</Label>
                        <div class="relative">
                            <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Zoek op naam, omschrijving..."
                                class="pl-8"
                            />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="category">Categorie</Label>
                        <Select v-model="selectedCategory">
                            <SelectTrigger>
                                <SelectValue placeholder="Alle categorieën" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id.toString()"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-end">
                        <Button
                            variant="outline"
                            @click="clearFilters"
                            :disabled="!selectedCategory && !searchQuery"
                        >
                            Filters wissen
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div v-if="products.length === 0" class="rounded-lg border border-dashed p-12 text-center">
                <p class="text-muted-foreground">Geen producten gevonden</p>
                <p class="text-sm text-muted-foreground mt-2">
                    Probeer andere zoekfilters
                </p>
            </div>

            <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <ProductCard
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                    :show-actions="true"
                    @favorite-toggle="handleFavoriteToggle(product.id)"
                    @view-details="handleViewDetails(product.id)"
                    @add-to-cart="handleAddToCart(product.id)"
                />
            </div>
        </div>
    </AppLayout>
</template>
