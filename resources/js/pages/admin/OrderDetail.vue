<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Trash2, Search } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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

interface OrderItem {
    id: number;
    product_id: number;
    product_title: string;
    product_thumbnail: string | null;
    quantity: number;
    price: string;
    subtotal: number;
}

interface Product {
    id: number;
    title: string;
    price: string;
}

interface DeliveryAddress {
    name: string;
    street_name: string;
    house_number: string;
    postal_code: string;
    city: string;
}

interface Customer {
    company_name: string;
    contact_person: string;
    phone_number: string;
    email: string | null;
}

interface Order {
    id: number;
    order_number: string;
    created_at: string;
    total: string;
    status: string;
    status_label: string;
    notes: string | null;
    customer: Customer;
    delivery_address: DeliveryAddress;
    items: OrderItem[];
}

const props = defineProps<{
    order: Order;
    availableProducts: Product[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Bestellingen',
        href: '/admin/orders',
    },
    {
        title: props.order.order_number,
    },
];

const selectedStatus = ref(props.order.status);
const processing = ref(false);
const editDialogOpen = ref(false);
const productSearchQuery = ref('');
const showProductDropdown = ref(false);

const editForm = useForm({
    items: props.order.items.map(item => ({
        id: item.id,
        product_id: item.product_id,
        quantity: item.quantity,
    })),
    notes: props.order.notes || '',
});

const filteredProducts = computed(() => {
    if (!productSearchQuery.value) {
        return props.availableProducts.slice(0, 10); // Show first 10 when no search
    }
    const query = productSearchQuery.value.toLowerCase();
    return props.availableProducts
        .filter(product => product.title.toLowerCase().includes(query))
        .slice(0, 10); // Limit to 10 results
});

const canEditOrder = () => {
    return !['completed', 'cancelled'].includes(props.order.status);
};

const openEditDialog = () => {
    editForm.items = props.order.items.map(item => ({
        id: item.id,
        product_id: item.product_id,
        quantity: item.quantity,
    }));
    editForm.notes = props.order.notes || '';
    productSearchQuery.value = '';
    showProductDropdown.value = false;
    editDialogOpen.value = true;
};

const addItem = (productId: number) => {
    const product = props.availableProducts.find(p => p.id === productId);
    if (!product) return;

    // Check if product already exists in order
    const existingItem = editForm.items.find(item => item.product_id === productId);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        editForm.items.push({
            id: null as any,
            product_id: product.id,
            quantity: 1,
        });
    }

    productSearchQuery.value = '';
    showProductDropdown.value = false;
};

const onSearchFocus = () => {
    showProductDropdown.value = true;
};

const onSearchBlur = () => {
    // Delay to allow click on dropdown items
    setTimeout(() => {
        showProductDropdown.value = false;
    }, 200);
};

const removeItem = (index: number) => {
    editForm.items.splice(index, 1);
};

const submitEdit = () => {
    editForm.patch(`/admin/orders/${props.order.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
        },
    });
};

const formatPrice = (price: string | number) => {
    return new Intl.NumberFormat('nl-NL', {
        style: 'currency',
        currency: 'EUR',
    }).format(typeof price === 'string' ? parseFloat(price) : price);
};

const getStatusVariant = (status: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive'> = {
        pending: 'secondary',
        confirmed: 'default',
        completed: 'default',
        cancelled: 'destructive',
    };
    return variants[status] || 'secondary';
};

const updateStatus = () => {
    if (selectedStatus.value === props.order.status) {
        return;
    }

    processing.value = true;

    router.patch(
        `/admin/orders/${props.order.id}/status`,
        { status: selectedStatus.value },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        }
    );
};

const backToOrders = () => {
    router.visit('/admin/orders');
};
</script>

<template>
    <Head :title="`Bestelling ${order.order_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Bestelling {{ order.order_number }}</h1>
                    <p class="text-sm text-muted-foreground">
                        Geplaatst op {{ order.created_at }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button v-if="canEditOrder()" variant="outline" @click="openEditDialog">
                        Bestelling aanpassen
                    </Button>

                    <Button variant="outline" @click="backToOrders">
                        ← Terug naar bestellingen
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Order Items -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-lg border p-6">
                        <h2 class="text-lg font-semibold mb-4">Producten</h2>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-20">Foto</TableHead>
                                    <TableHead>Product</TableHead>
                                    <TableHead>Prijs</TableHead>
                                    <TableHead>Aantal</TableHead>
                                    <TableHead>Subtotaal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, index) in order.items" :key="index">
                                    <TableCell>
                                        <img
                                            v-if="item.product_thumbnail"
                                            :src="item.product_thumbnail"
                                            :alt="item.product_title"
                                            class="h-12 w-12 rounded object-cover"
                                        />
                                        <div
                                            v-else
                                            class="h-12 w-12 rounded bg-muted flex items-center justify-center text-muted-foreground text-xs"
                                        >
                                            Geen foto
                                        </div>
                                    </TableCell>
                                    <TableCell class="font-medium">{{ item.product_title }}</TableCell>
                                    <TableCell>{{ formatPrice(item.price) }}</TableCell>
                                    <TableCell>{{ item.quantity }}x</TableCell>
                                    <TableCell class="font-medium">{{ formatPrice(item.subtotal) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Customer Info -->
                    <div class="rounded-lg border p-6">
                        <h2 class="text-lg font-semibold mb-4">Klantgegevens</h2>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="font-medium">Bedrijf:</span>
                                <span class="ml-2">{{ order.customer.company_name }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Contactpersoon:</span>
                                <span class="ml-2">{{ order.customer.contact_person }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Telefoon:</span>
                                <span class="ml-2">{{ order.customer.phone_number }}</span>
                            </div>
                            <div v-if="order.customer.email">
                                <span class="font-medium">Email:</span>
                                <span class="ml-2">{{ order.customer.email }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="order.notes" class="rounded-lg border p-6">
                        <h2 class="text-lg font-semibold mb-2">Opmerkingen</h2>
                        <p class="text-muted-foreground">{{ order.notes }}</p>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="space-y-6">
                    <!-- Status Update -->
                    <div class="rounded-lg border p-6">
                        <h2 class="text-lg font-semibold mb-4">Status</h2>
                        <div class="space-y-4">
                            <div>
                                <Badge :variant="getStatusVariant(order.status)" class="text-sm">
                                    {{ order.status_label }}
                                </Badge>
                            </div>
                            <div class="space-y-2">
                                <Label for="status">Status wijzigen</Label>
                                <Select v-model="selectedStatus">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pending">In behandeling</SelectItem>
                                        <SelectItem value="confirmed">Bevestigd</SelectItem>
                                        <SelectItem value="completed">Voltooid</SelectItem>
                                        <SelectItem value="cancelled">Geannuleerd</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button
                                    size="sm"
                                    class="w-full"
                                    @click="updateStatus"
                                    :disabled="processing || selectedStatus === order.status"
                                >
                                    {{ processing ? 'Bezig...' : 'Status bijwerken' }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Address -->
                    <div class="rounded-lg border p-6">
                        <h2 class="text-lg font-semibold mb-4">Afleveradres</h2>
                        <div class="space-y-1 text-sm">
                            <p class="font-medium">{{ order.delivery_address.name }}</p>
                            <p>{{ order.delivery_address.street_name }} {{ order.delivery_address.house_number }}</p>
                            <p>{{ order.delivery_address.postal_code }} {{ order.delivery_address.city }}</p>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="rounded-lg border p-6">
                        <h2 class="text-lg font-semibold mb-4">Totaal</h2>
                        <div class="flex justify-between items-center text-lg font-bold">
                            <span>Totaalbedrag:</span>
                            <span>{{ formatPrice(order.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Order Dialog -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Bestelling aanpassen</DialogTitle>
                    <DialogDescription>
                        Pas de aantallen en opmerkingen van deze bestelling aan.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-6">
                    <div>
                        <Label class="text-base font-semibold mb-4 block">Producten</Label>

                        <div class="relative w-full mb-4">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="productSearchQuery"
                                    type="text"
                                    placeholder="Zoek product om toe te voegen..."
                                    class="pl-10"
                                    @focus="onSearchFocus"
                                    @blur="onSearchBlur"
                                    :disabled="editForm.processing"
                                />
                            </div>

                            <!-- Dropdown Results -->
                            <div
                                v-if="showProductDropdown && filteredProducts.length > 0"
                                class="absolute z-50 w-full mt-1 bg-popover border rounded-md shadow-md max-h-[300px] overflow-y-auto"
                            >
                                <div
                                    v-for="product in filteredProducts"
                                    :key="product.id"
                                    class="px-3 py-2 hover:bg-accent cursor-pointer flex items-center justify-between"
                                    @click="addItem(product.id)"
                                >
                                    <span class="font-medium">{{ product.title }}</span>
                                    <span class="text-sm text-muted-foreground">{{ formatPrice(product.price) }}</span>
                                </div>
                            </div>

                            <!-- No Results -->
                            <div
                                v-if="showProductDropdown && productSearchQuery && filteredProducts.length === 0"
                                class="absolute z-50 w-full mt-1 bg-popover border rounded-md shadow-md p-3 text-sm text-muted-foreground text-center"
                            >
                                Geen producten gevonden
                            </div>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Product</TableHead>
                                    <TableHead>Prijs</TableHead>
                                    <TableHead class="w-32">Aantal</TableHead>
                                    <TableHead class="w-20"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(formItem, index) in editForm.items" :key="index">
                                    <TableCell class="font-medium">
                                        {{
                                            formItem.id
                                                ? order.items.find(item => item.id === formItem.id)?.product_title
                                                : availableProducts.find(p => p.id === formItem.product_id)?.title
                                        }}
                                    </TableCell>
                                    <TableCell>
                                        {{
                                            formItem.id
                                                ? formatPrice(order.items.find(item => item.id === formItem.id)?.price || 0)
                                                : formatPrice(availableProducts.find(p => p.id === formItem.product_id)?.price || 0)
                                        }}
                                    </TableCell>
                                    <TableCell>
                                        <Input
                                            type="number"
                                            v-model.number="formItem.quantity"
                                            min="1"
                                            step="1"
                                            :disabled="editForm.processing"
                                        />
                                        <InputError :message="editForm.errors[`items.${index}.quantity`]" class="mt-1" />
                                    </TableCell>
                                    <TableCell>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="removeItem(index)"
                                            :disabled="editForm.processing || editForm.items.length <= 1"
                                        >
                                            <Trash2 class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div>
                        <Label for="notes" class="mb-[15px] block">Opmerkingen</Label>
                        <Textarea
                            id="notes"
                            v-model="editForm.notes"
                            placeholder="Optionele opmerkingen over deze bestelling..."
                            rows="4"
                            :disabled="editForm.processing"
                        />
                        <InputError :message="editForm.errors.notes" class="mt-1" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="editDialogOpen = false"
                            :disabled="editForm.processing"
                        >
                            Annuleren
                        </Button>
                        <Button type="submit" :disabled="editForm.processing">
                            {{ editForm.processing ? 'Bezig...' : 'Opslaan' }}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
