<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Zap, ShoppingCart, Minus, Plus, Clock } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useCart } from '@/composables/useCart';

interface NutritionFacts {
    energy?: string;
    fat?: string;
    saturated_fat?: string;
    carbohydrates?: string;
    sugars?: string;
    protein?: string;
    salt?: string;
    fiber?: string;
}

interface Product {
    id: number;
    title: string;
    category: string | null;
    category_id: number | null;
    price: string;
    description: string;
    ingredients: string[] | null;
    allergens: string[] | null;
    nutrition_facts?: NutritionFacts;
    weight: string | null;
    article_number: string;
    photo_url: string | null;
    thumbnail_url: string | null;
    is_in_stock: boolean;
    is_favorite: boolean;
    in_cart: boolean;
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
    product: Product;
    orderDeadline: OrderDeadline;
}>();

const { addToCart: addProductToCart } = useCart();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Producten',
        href: '/customer/products',
    },
    {
        title: props.product.title,
    },
];

const quantity = ref(1);

const formattedPrice = computed(() => {
    return new Intl.NumberFormat('nl-NL', {
        style: 'currency',
        currency: 'EUR',
    }).format(parseFloat(props.product.price));
});

const totalPrice = computed(() => {
    const total = parseFloat(props.product.price) * quantity.value;
    return new Intl.NumberFormat('nl-NL', {
        style: 'currency',
        currency: 'EUR',
    }).format(total);
});

const incrementQuantity = () => {
    quantity.value++;
};

const decrementQuantity = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const toggleFavorite = () => {
    router.post(`/customer/favorites/${props.product.id}/toggle`, {}, {
        preserveScroll: true,
    });
};

const addToCart = () => {
    addProductToCart(props.product.id, quantity.value, {
        onSuccess: () => {
            // Reset quantity to 1 after successful add
            quantity.value = 1;
        },
    });
};

const backToProducts = () => {
    router.visit('/customer/products');
};
</script>

<template>
    <Head :title="product.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <Button variant="outline" @click="backToProducts">
                    ← Terug naar producten
                </Button>
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

            <div class="grid gap-8 lg:grid-cols-2">
                <!-- Product Image -->
                <div class="sticky top-6 self-start rounded-lg border overflow-hidden bg-muted">
                    <img
                        v-if="product.photo_url"
                        :src="product.photo_url"
                        :alt="product.title"
                        class="w-full h-auto object-contain"
                    />
                    <div
                        v-else
                        class="w-full aspect-square flex items-center justify-center text-muted-foreground"
                    >
                        <span class="text-4xl">Geen foto beschikbaar</span>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="space-y-6">
                    <!-- Title and Category -->
                    <div>
                        <div class="flex items-start justify-between gap-4 mb-2">
                            <h1 class="text-3xl font-bold">{{ product.title }}</h1>
                            <Button
                                size="icon"
                                variant="outline"
                                @click="toggleFavorite"
                            >
                                <Zap
                                    :class="[
                                        'h-5 w-5',
                                        product.is_favorite ? 'fill-yellow-500 text-yellow-500' : '',
                                    ]"
                                />
                            </Button>
                        </div>
                        <p v-if="product.category" class="text-sm text-muted-foreground">
                            {{ product.category }}
                        </p>
                    </div>

                    <!-- Price and Stock -->
                    <div class="flex items-center gap-4">
                        <div class="flex flex-col">
                            <span class="text-4xl font-bold text-primary">
                                {{ formattedPrice }}
                            </span>
                            <span class="text-xs text-muted-foreground">ex. BTW</span>
                        </div>
                        <Badge :variant="product.is_in_stock ? 'default' : 'destructive'">
                            {{ product.is_in_stock ? 'Op voorraad' : 'Niet op voorraad' }}
                        </Badge>
                    </div>

                    <Separator />

                    <!-- Product Info -->
                    <div class="space-y-3">
                        <div v-if="product.weight" class="flex gap-2">
                            <span class="text-sm font-medium">Gewicht:</span>
                            <span class="text-sm text-muted-foreground">{{ product.weight }}</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-sm font-medium">Artikelnummer:</span>
                            <span class="text-sm text-muted-foreground font-mono">{{ product.article_number }}</span>
                        </div>
                    </div>

                    <Separator />

                    <!-- Description -->
                    <div>
                        <h2 class="text-lg font-semibold mb-2">Omschrijving</h2>
                        <p class="text-muted-foreground whitespace-pre-line">{{ product.description }}</p>
                    </div>

                    <!-- Ingredients -->
                    <div v-if="product.ingredients && product.ingredients.length > 0">
                        <h2 class="text-lg font-semibold mb-2">Ingrediënten</h2>
                        <p class="text-sm text-muted-foreground">
                            {{ product.ingredients.join(', ') }}
                        </p>
                    </div>

                    <!-- Allergens -->
                    <div v-if="product.allergens && product.allergens.length > 0" class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                        <h2 class="text-lg font-semibold mb-2 text-orange-900">Allergenen</h2>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="allergen in product.allergens"
                                :key="allergen"
                                variant="destructive"
                                class="bg-orange-600"
                            >
                                {{ allergen }}
                            </Badge>
                        </div>
                    </div>

                    <!-- Nutrition Facts -->
                    <div
                        v-if="product.nutrition_facts && Object.values(product.nutrition_facts).some(v => v)"
                        class="rounded-lg border p-4"
                    >
                        <h2 class="text-lg font-semibold mb-3">Voedingswaarde</h2>
                        <p class="text-xs text-muted-foreground mb-3">Per 100 gram</p>
                        <div class="space-y-2">
                            <div v-if="product.nutrition_facts.energy" class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Energie</span>
                                <span class="font-medium">{{ product.nutrition_facts.energy }} kcal</span>
                            </div>
                            <div v-if="product.nutrition_facts.fat" class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Vet</span>
                                <span class="font-medium">{{ product.nutrition_facts.fat }} g</span>
                            </div>
                            <div v-if="product.nutrition_facts.saturated_fat" class="flex justify-between text-sm pl-4">
                                <span class="text-muted-foreground">Waarvan verzadigd</span>
                                <span class="font-medium">{{ product.nutrition_facts.saturated_fat }} g</span>
                            </div>
                            <div v-if="product.nutrition_facts.carbohydrates" class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Koolhydraten</span>
                                <span class="font-medium">{{ product.nutrition_facts.carbohydrates }} g</span>
                            </div>
                            <div v-if="product.nutrition_facts.sugars" class="flex justify-between text-sm pl-4">
                                <span class="text-muted-foreground">Waarvan suikers</span>
                                <span class="font-medium">{{ product.nutrition_facts.sugars }} g</span>
                            </div>
                            <div v-if="product.nutrition_facts.protein" class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Eiwitten</span>
                                <span class="font-medium">{{ product.nutrition_facts.protein }} g</span>
                            </div>
                            <div v-if="product.nutrition_facts.salt" class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Zout</span>
                                <span class="font-medium">{{ product.nutrition_facts.salt }} g</span>
                            </div>
                            <div v-if="product.nutrition_facts.fiber" class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Vezel</span>
                                <span class="font-medium">{{ product.nutrition_facts.fiber }} g</span>
                            </div>
                        </div>
                    </div>

                    <Separator />

                    <!-- Add to Cart -->
                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label for="quantity">Aantal</Label>
                            <div class="flex items-center gap-2">
                                <Button
                                    size="icon"
                                    variant="outline"
                                    @click="decrementQuantity"
                                    :disabled="quantity <= 1"
                                >
                                    <Minus class="h-4 w-4" />
                                </Button>
                                <Input
                                    id="quantity"
                                    v-model.number="quantity"
                                    type="number"
                                    min="1"
                                    :max="product.stock_quantity"
                                    class="text-center w-20"
                                />
                                <Button
                                    size="icon"
                                    variant="outline"
                                    @click="incrementQuantity"
                                    :disabled="quantity >= product.stock_quantity"
                                >
                                    <Plus class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <Button
                                size="lg"
                                class="flex-1"
                                :disabled="!product.is_in_stock"
                                @click="addToCart"
                            >
                                <ShoppingCart class="h-5 w-5 mr-2" />
                                Toevoegen aan winkelwagen ({{ totalPrice }})
                            </Button>
                        </div>

                        <p v-if="product.in_cart" class="text-sm text-muted-foreground text-center">
                            Dit product zit al in je winkelwagen
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
