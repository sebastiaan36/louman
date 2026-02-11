<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
import { ref } from 'vue';

interface OrderItem {
    product_title: string;
    product_thumbnail: string | null;
    quantity: number;
    price: string;
    subtotal: number;
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
                <Button variant="outline" @click="backToOrders">
                    ← Terug naar bestellingen
                </Button>
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
    </AppLayout>
</template>
