<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ShoppingCart, Plus, Trash2, Search } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface DeliveryAddress {
    id: number;
    name: string;
    street_name: string;
    house_number: string;
    postal_code: string;
    city: string;
    is_default: boolean;
}

interface Customer {
    id: number;
    company_name: string;
    contact_person: string;
    customer_category: string | null;
    discount_percentage: string | null;
    delivery_addresses: DeliveryAddress[];
}

interface Product {
    id: number;
    title: string;
    price: string;
}

interface OrderItem {
    product_id: number;
    product_title: string;
    quantity: number;
    unit_price: number;
}

const props = defineProps<{
    customers: Customer[];
    products: Product[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bestellingen', href: '/admin/orders' },
    { title: 'Nieuwe bestelling' },
];

// Customer search
const customerSearch = ref('');
const selectedCustomer = ref<Customer | null>(null);
const showCustomerDropdown = ref(false);

const filteredCustomers = computed(() => {
    const q = customerSearch.value.toLowerCase();
    if (!q) return props.customers.slice(0, 10);
    return props.customers.filter(c =>
        c.company_name.toLowerCase().includes(q) ||
        c.contact_person.toLowerCase().includes(q)
    ).slice(0, 10);
});

const selectCustomer = (customer: Customer) => {
    selectedCustomer.value = customer;
    customerSearch.value = customer.company_name;
    showCustomerDropdown.value = false;

    // Auto-select default delivery address
    const defaultAddr = customer.delivery_addresses.find(a => a.is_default)
        ?? customer.delivery_addresses[0]
        ?? null;
    selectedDeliveryAddressId.value = defaultAddr?.id ?? null;

    // Recalculate prices for existing items
    orderItems.value = orderItems.value.map(item => ({
        ...item,
        unit_price: getPriceForCustomer(item.product_id),
    }));
};

const clearCustomer = () => {
    selectedCustomer.value = null;
    customerSearch.value = '';
    selectedDeliveryAddressId.value = null;
};

// Delivery address
const selectedDeliveryAddressId = ref<number | null>(null);

// Product search
const productSearch = ref('');
const showProductDropdown = ref(false);

const filteredProducts = computed(() => {
    const q = productSearch.value.toLowerCase();
    if (!q) return [];
    return props.products.filter(p =>
        p.title.toLowerCase().includes(q)
    ).slice(0, 8);
});

const getPriceForCustomer = (productId: number): number => {
    const product = props.products.find(p => p.id === productId);
    if (!product || !selectedCustomer.value) return 0;

    let basePrice = parseFloat(product.price);

    const discount = parseFloat(selectedCustomer.value.discount_percentage ?? '0');
    if (discount > 0) {
        basePrice = basePrice * (1 - discount / 100);
    }

    return Math.round(basePrice * 100) / 100;
};

// Order items
const orderItems = ref<OrderItem[]>([]);

const addProduct = (product: Product) => {
    const existing = orderItems.value.find(i => i.product_id === product.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        orderItems.value.push({
            product_id: product.id,
            product_title: product.title,
            quantity: 1,
            unit_price: getPriceForCustomer(product.id),
        });
    }
    productSearch.value = '';
    showProductDropdown.value = false;
};

const removeItem = (index: number) => {
    orderItems.value.splice(index, 1);
};

const orderTotal = computed(() => {
    return orderItems.value.reduce((sum, item) => sum + item.unit_price * item.quantity, 0);
});

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(price);
};

// Notes
const notes = ref('');

// Errors
const errors = ref<Record<string, string>>({});

const submitting = ref(false);

const submit = () => {
    errors.value = {};

    if (!selectedCustomer.value) {
        errors.value.customer = 'Selecteer een klant.';
        return;
    }
    if (orderItems.value.length === 0) {
        errors.value.items = 'Voeg minimaal één product toe.';
        return;
    }

    submitting.value = true;

    router.post('/admin/orders', {
        customer_id: selectedCustomer.value.id,
        delivery_address_id: selectedDeliveryAddressId.value,
        items: orderItems.value.map(i => ({
            product_id: i.product_id,
            quantity: i.quantity,
        })),
        notes: notes.value || null,
    }, {
        onError: (e) => {
            errors.value = e;
            submitting.value = false;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
};
</script>

<template>
    <Head title="Nieuwe bestelling" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-3xl">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <ShoppingCart class="h-6 w-6" />
                    Nieuwe bestelling
                </h1>
                <p class="text-sm text-muted-foreground">Maak een bestelling aan namens een klant</p>
            </div>

            <div class="rounded-lg border p-6 space-y-6">

                <!-- Customer search -->
                <div class="space-y-2">
                    <Label>Klant <span class="text-destructive">*</span></Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="customerSearch"
                            placeholder="Zoek op bedrijfsnaam of contactpersoon..."
                            class="pl-9"
                            @focus="showCustomerDropdown = true"
                            @blur="setTimeout(() => showCustomerDropdown = false, 150)"
                            autocomplete="off"
                        />
                        <!-- Dropdown -->
                        <div
                            v-if="showCustomerDropdown && filteredCustomers.length > 0"
                            class="absolute z-50 mt-1 w-full rounded-md border bg-white shadow-lg"
                        >
                            <button
                                v-for="customer in filteredCustomers"
                                :key="customer.id"
                                type="button"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-muted flex flex-col"
                                @mousedown.prevent="selectCustomer(customer)"
                            >
                                <span class="font-medium">{{ customer.company_name }}</span>
                                <span class="text-muted-foreground text-xs">{{ customer.contact_person }}</span>
                            </button>
                        </div>
                    </div>
                    <p v-if="errors.customer" class="text-sm text-destructive">{{ errors.customer }}</p>

                    <!-- Selected customer info -->
                    <div v-if="selectedCustomer" class="rounded-md bg-muted px-4 py-3 text-sm flex items-center justify-between">
                        <div>
                            <span class="font-medium">{{ selectedCustomer.company_name }}</span>
                            <span class="text-muted-foreground ml-2">{{ selectedCustomer.contact_person }}</span>
                            <span v-if="selectedCustomer.customer_category" class="ml-2 text-xs text-muted-foreground capitalize">({{ selectedCustomer.customer_category }})</span>
                        </div>
                        <button type="button" @click="clearCustomer" class="text-muted-foreground hover:text-foreground text-xs">&times; Wissen</button>
                    </div>
                </div>

                <!-- Delivery address -->
                <div v-if="selectedCustomer" class="space-y-2">
                    <Label>Afleveradres</Label>
                    <div v-if="selectedCustomer.delivery_addresses.length === 0" class="text-sm text-muted-foreground">
                        Geen afleveradressen — hoofdadres wordt gebruikt
                    </div>
                    <div v-else class="space-y-2">
                        <label
                            v-for="addr in selectedCustomer.delivery_addresses"
                            :key="addr.id"
                            class="flex items-start gap-3 rounded-md border p-3 cursor-pointer"
                            :class="selectedDeliveryAddressId === addr.id ? 'border-primary bg-primary/5' : ''"
                        >
                            <input
                                type="radio"
                                :value="addr.id"
                                v-model="selectedDeliveryAddressId"
                                class="mt-0.5"
                            />
                            <div class="text-sm">
                                <div class="font-medium">{{ addr.name }}</div>
                                <div class="text-muted-foreground">{{ addr.street_name }} {{ addr.house_number }}, {{ addr.postal_code }} {{ addr.city }}</div>
                                <div v-if="addr.is_default" class="text-xs text-primary mt-0.5">Standaard</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Product search -->
                <div class="space-y-2">
                    <Label>Producten <span class="text-destructive">*</span></Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="productSearch"
                            placeholder="Zoek product op naam..."
                            class="pl-9"
                            @focus="showProductDropdown = true"
                            @blur="setTimeout(() => showProductDropdown = false, 150)"
                            autocomplete="off"
                            :disabled="!selectedCustomer"
                        />
                        <div
                            v-if="showProductDropdown && filteredProducts.length > 0"
                            class="absolute z-50 mt-1 w-full rounded-md border bg-white shadow-lg"
                        >
                            <button
                                v-for="product in filteredProducts"
                                :key="product.id"
                                type="button"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-muted flex justify-between items-center"
                                @mousedown.prevent="addProduct(product)"
                            >
                                <span>{{ product.title }}</span>
                                <span class="text-muted-foreground text-xs ml-4">
                                    {{ formatPrice(getPriceForCustomer(product.id)) }}
                                </span>
                            </button>
                        </div>
                    </div>
                    <p v-if="!selectedCustomer" class="text-xs text-muted-foreground">Selecteer eerst een klant om de juiste prijzen te berekenen.</p>
                    <p v-if="errors.items" class="text-sm text-destructive">{{ errors.items }}</p>
                </div>

                <!-- Items list -->
                <div v-if="orderItems.length > 0" class="space-y-2">
                    <div
                        v-for="(item, index) in orderItems"
                        :key="item.product_id"
                        class="flex items-center gap-3 rounded-md border px-3 py-2"
                    >
                        <div class="flex-1 text-sm font-medium">{{ item.product_title }}</div>
                        <div class="text-sm text-muted-foreground w-20 text-right">
                            {{ formatPrice(item.unit_price) }}
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                class="h-6 w-6 rounded border text-sm flex items-center justify-center hover:bg-muted"
                                @click="item.quantity = Math.max(1, item.quantity - 1)"
                            >−</button>
                            <input
                                type="number"
                                v-model.number="item.quantity"
                                min="1"
                                class="w-12 text-center text-sm border rounded px-1 py-0.5"
                            />
                            <button
                                type="button"
                                class="h-6 w-6 rounded border text-sm flex items-center justify-center hover:bg-muted"
                                @click="item.quantity += 1"
                            >+</button>
                        </div>
                        <div class="text-sm font-medium w-20 text-right">
                            {{ formatPrice(item.unit_price * item.quantity) }}
                        </div>
                        <button type="button" @click="removeItem(index)" class="text-destructive hover:opacity-70 ml-1">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Total -->
                    <div class="flex justify-end pt-2 border-t">
                        <span class="text-sm font-bold">Totaal: {{ formatPrice(orderTotal) }}</span>
                    </div>
                </div>

                <!-- Notes -->
                <div class="space-y-2">
                    <Label for="notes">Bestelnotitie</Label>
                    <Textarea
                        id="notes"
                        v-model="notes"
                        placeholder="Optionele opmerkingen bij de bestelling..."
                        rows="3"
                    />
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                    <Button @click="submit" :disabled="submitting">
                        <Plus class="h-4 w-4 mr-2" />
                        Bestelling aanmaken
                    </Button>
                    <Button variant="outline" as="a" href="/admin/orders">Annuleren</Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
