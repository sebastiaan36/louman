<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ShoppingCart, Trash2, Minus, Plus, MapPin, Clock } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface CartItem {
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

interface DeliveryAddress {
    id: number;
    name: string;
    street: string;
    house_number: string;
    postal_code: string;
    city: string;
    is_default: boolean;
}

interface MainAddress {
    street: string;
    house_number: string;
    postal_code: string;
    city: string;
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
    cartItems: CartItem[];
    total: string;
    itemCount: number;
    deliveryAddresses: DeliveryAddress[];
    mainAddress: MainAddress;
    orderDeadline: OrderDeadline;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Winkelwagen',
    },
];

const quantities = ref<Record<number, number>>(
    Object.fromEntries(props.cartItems.map((item) => [item.id, item.quantity]))
);

// Selected delivery address ('main' means use main address)
const selectedDeliveryAddressId = ref<string>('main');

const selectedAddress = computed(() => {
    if (selectedDeliveryAddressId.value === 'main') {
        return {
            name: 'Factuuradres',
            fullAddress: `${props.mainAddress.street} ${props.mainAddress.house_number}, ${props.mainAddress.postal_code} ${props.mainAddress.city}`,
        };
    }

    const addr = props.deliveryAddresses.find(a => a.id.toString() === selectedDeliveryAddressId.value);
    if (addr) {
        return {
            name: addr.name,
            fullAddress: `${addr.street} ${addr.house_number}, ${addr.postal_code} ${addr.city}`,
        };
    }

    // Fallback to main address
    return {
        name: 'Factuuradres',
        fullAddress: `${props.mainAddress.street} ${props.mainAddress.house_number}, ${props.mainAddress.postal_code} ${props.mainAddress.city}`,
    };
});

const VAT_RATE = 0.09;

const vatAmount = computed(() => {
    const total = parseFloat(props.total);
    return total * VAT_RATE;
});

const totalInclVat = computed(() => {
    const total = parseFloat(props.total);
    return total * (1 + VAT_RATE);
});

const formatPrice = (price: string | number) => {
    return new Intl.NumberFormat('nl-NL', {
        style: 'currency',
        currency: 'EUR',
    }).format(typeof price === 'string' ? parseFloat(price) : price);
};

const updateQuantity = (cartItemId: number, newQuantity: number) => {
    router.patch(
        `/customer/cart/${cartItemId}`,
        { quantity: newQuantity },
        {
            preserveScroll: true,
            onSuccess: () => {
                quantities.value[cartItemId] = newQuantity;
            },
        }
    );
};

const incrementQuantity = (item: CartItem) => {
    const newQuantity = quantities.value[item.id] + 1;
    updateQuantity(item.id, newQuantity);
};

const decrementQuantity = (item: CartItem) => {
    if (quantities.value[item.id] > 1) {
        const newQuantity = quantities.value[item.id] - 1;
        updateQuantity(item.id, newQuantity);
    }
};

const removeItem = (cartItemId: number) => {
    if (confirm('Weet je zeker dat je dit product wilt verwijderen?')) {
        router.delete(`/customer/cart/${cartItemId}`, {
            preserveScroll: true,
        });
    }
};

const clearCart = () => {
    if (confirm('Weet je zeker dat je de hele winkelwagen wilt legen?')) {
        router.delete('/customer/cart', {
            preserveScroll: true,
        });
    }
};

const showCheckoutDialog = ref(false);
const checkoutForm = ref({
    delivery_address_id: null as string | null,
    notes: '',
});
const processing = ref(false);

const proceedToCheckout = () => {
    // Set delivery address for checkout ('main' becomes null for backend)
    checkoutForm.value.delivery_address_id = selectedDeliveryAddressId.value === 'main'
        ? null
        : selectedDeliveryAddressId.value;
    showCheckoutDialog.value = true;
};

const submitOrder = () => {
    console.log('submitOrder called');
    processing.value = true;

    // Use selected address or null for main address
    const orderData = {
        delivery_address_id: checkoutForm.value.delivery_address_id,
        notes: checkoutForm.value.notes,
    };

    console.log('Order data:', orderData);
    console.log('Posting to /customer/orders');

    router.post('/customer/orders', orderData, {
        onBefore: () => {
            console.log('Request starting...');
        },
        onStart: () => {
            console.log('Request started');
        },
        onSuccess: (page) => {
            console.log('Order placed successfully');
            console.log('Response:', page);
            // Manually navigate to orders page
            router.visit('/customer/orders');
        },
        onError: (errors) => {
            console.log('Order error:', errors);
            processing.value = false;
        },
        onFinish: () => {
            console.log('Request finished');
            processing.value = false;
        },
    });
};
</script>

<template>
    <Head title="Winkelwagen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <ShoppingCart class="h-6 w-6" />
                    Winkelwagen
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ itemCount }} {{ itemCount === 1 ? 'product' : 'producten' }} in je winkelwagen
                </p>
            </div>

            <!-- Empty State -->
            <div v-if="cartItems.length === 0" class="rounded-lg border border-dashed p-12 text-center space-y-4">
                <ShoppingCart class="h-16 w-16 mx-auto text-muted-foreground" />
                <div>
                    <p class="text-lg font-medium text-muted-foreground">Je winkelwagen is leeg</p>
                    <p class="text-sm text-muted-foreground mt-2">
                        Voeg producten toe om een bestelling te plaatsen
                    </p>
                </div>
                <Link href="/customer/products">
                    <Button>
                        Blader door producten
                    </Button>
                </Link>
            </div>

            <!-- Cart Items -->
            <div v-else class="grid gap-6 lg:grid-cols-3">
                <!-- Main Cart Section -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-24">Foto</TableHead>
                                    <TableHead>Product</TableHead>
                                    <TableHead>Prijs</TableHead>
                                    <TableHead class="w-40">Aantal</TableHead>
                                    <TableHead>Subtotaal</TableHead>
                                    <TableHead class="w-20"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in cartItems" :key="item.id">
                                    <TableCell>
                                        <img
                                            v-if="item.product_thumbnail"
                                            :src="item.product_thumbnail"
                                            :alt="item.product_title"
                                            class="h-16 w-16 rounded object-cover"
                                        />
                                        <div
                                            v-else
                                            class="h-16 w-16 rounded bg-muted flex items-center justify-center text-muted-foreground text-xs"
                                        >
                                            Geen foto
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div>
                                            <Link
                                                :href="`/customer/products/${item.product_id}`"
                                                class="font-medium hover:underline"
                                            >
                                                {{ item.product_title }}
                                            </Link>
                                            <p v-if="item.product_weight" class="text-sm text-muted-foreground">
                                                {{ item.product_weight }}
                                            </p>
                                            <Badge v-if="!item.is_available" variant="destructive" class="mt-1">
                                                Niet beschikbaar
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {{ formatPrice(item.product_price) }}
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Button
                                                size="icon"
                                                variant="outline"
                                                @click="decrementQuantity(item)"
                                                :disabled="quantities[item.id] <= 1"
                                            >
                                                <Minus class="h-4 w-4" />
                                            </Button>
                                            <Input
                                                v-model="quantities[item.id]"
                                                type="number"
                                                min="1"
                                                class="text-center w-16"
                                                @blur="updateQuantity(item.id, quantities[item.id])"
                                            />
                                            <Button
                                                size="icon"
                                                variant="outline"
                                                @click="incrementQuantity(item)"
                                            >
                                                <Plus class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                    <TableCell class="font-medium">
                                        {{ formatPrice(item.subtotal) }}
                                    </TableCell>
                                    <TableCell>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            @click="removeItem(item.id)"
                                        >
                                            <Trash2 class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <Button variant="outline" @click="clearCart">
                        <Trash2 class="h-4 w-4 mr-2" />
                        Winkelwagen legen
                    </Button>
                </div>

                <!-- Sidebar with Summary and Delivery -->
                <div class="space-y-6">
                    <!-- Delivery Address Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <MapPin class="h-5 w-5" />
                                Afleveradres
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-3">
                                <Label>Selecteer afleveradres</Label>
                                <Select v-model="selectedDeliveryAddressId">
                                    <SelectTrigger>
                                        <SelectValue :placeholder="selectedAddress.name" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="main">
                                            Factuuradres
                                        </SelectItem>
                                        <SelectItem
                                            v-for="address in deliveryAddresses"
                                            :key="address.id"
                                            :value="address.id.toString()"
                                        >
                                            <div class="flex items-center gap-2">
                                                <span>{{ address.name }}</span>
                                                <Badge v-if="address.is_default" variant="secondary" class="text-xs">
                                                    Standaard
                                                </Badge>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div class="text-sm text-muted-foreground">
                                    {{ selectedAddress.fullAddress }}
                                </div>
                                <Link href="/customer/delivery-addresses">
                                    <Button variant="link" size="sm" class="p-0 h-auto">
                                        Beheer afleveradressen
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Order Deadline Card -->
                    <Card :class="{ 'border-orange-500': orderDeadline.is_urgent }">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Clock class="h-5 w-5" :class="{ 'text-orange-500': orderDeadline.is_urgent }" />
                                Besteldeadline
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 py-3">
                            <p class="text-sm text-muted-foreground">
                                Bestel voor maandag 12:00 uur voor levering
                            </p>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="text-center p-3 rounded-lg bg-muted">
                                    <div class="text-2xl font-bold" :class="{ 'text-orange-500': orderDeadline.is_urgent }">
                                        {{ orderDeadline.days }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ orderDeadline.days === 1 ? 'dag' : 'dagen' }}
                                    </div>
                                </div>
                                <div class="text-center p-3 rounded-lg bg-muted">
                                    <div class="text-2xl font-bold" :class="{ 'text-orange-500': orderDeadline.is_urgent }">
                                        {{ orderDeadline.hours }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ orderDeadline.hours === 1 ? 'uur' : 'uren' }}
                                    </div>
                                </div>
                                <div class="text-center p-3 rounded-lg bg-muted">
                                    <div class="text-2xl font-bold" :class="{ 'text-orange-500': orderDeadline.is_urgent }">
                                        {{ orderDeadline.minutes }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ orderDeadline.minutes === 1 ? 'min' : 'min' }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="orderDeadline.is_urgent" class="text-sm text-orange-600 font-medium text-center">
                                ⚠️ Minder dan 24 uur resterend!
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Order Summary Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Bestelling</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 py-3">
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Aantal producten:</span>
                                    <span>{{ itemCount }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">Subtotaal (ex. BTW):</span>
                                    <span>{{ formatPrice(total) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-muted-foreground">BTW (9%):</span>
                                    <span>{{ formatPrice(vatAmount) }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold pt-2 border-t">
                                    <span>Totaal (incl. BTW):</span>
                                    <span>{{ formatPrice(totalInclVat) }}</span>
                                </div>
                            </div>

                            <Button size="lg" class="w-full" @click="proceedToCheckout">
                                Bestelling plaatsen
                            </Button>
                            <p class="text-xs text-center text-muted-foreground">
                                Betaling wordt offline verwerkt
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Checkout Dialog -->
        <Dialog v-model:open="showCheckoutDialog">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Bestelling plaatsen</DialogTitle>
                    <DialogDescription>
                        Controleer je afleveradres en plaats je bestelling
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="rounded-lg border p-4 bg-muted/50">
                        <Label class="text-sm font-medium">Afleveradres</Label>
                        <p class="text-sm mt-1">{{ selectedAddress.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ selectedAddress.fullAddress }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Opmerkingen (optioneel)</Label>
                        <Textarea
                            id="notes"
                            v-model="checkoutForm.notes"
                            placeholder="Bijv. afleverinstructies..."
                            rows="3"
                        />
                    </div>

                    <div class="rounded-lg border p-4 bg-muted/50 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Subtotaal (ex. BTW):</span>
                            <span>{{ formatPrice(total) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">BTW (9%):</span>
                            <span>{{ formatPrice(vatAmount) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="font-medium">Totaal (incl. BTW):</span>
                            <span class="text-lg font-bold">{{ formatPrice(totalInclVat) }}</span>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showCheckoutDialog = false" :disabled="processing">
                        Annuleren
                    </Button>
                    <Button @click="submitOrder" :disabled="processing">
                        {{ processing ? 'Bezig...' : 'Bestelling plaatsen' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
