<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { computed } from 'vue';
import { Download } from 'lucide-vue-next';

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

interface Order {
    id: number;
    order_number: string;
    created_at: string;
    total: string;
    status: string;
    status_label: string;
    notes: string | null;
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
        title: 'Mijn Bestellingen',
        href: '/customer/orders',
    },
    {
        title: props.order.order_number,
    },
];

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

const backToOrders = () => {
    router.visit('/customer/orders');
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
                <a
                    v-if="order.status === 'completed'"
                    :href="`/customer/orders/${order.id}/invoice`"
                    target="_blank"
                >
                    <Button variant="outline">
                        <Download class="h-4 w-4 mr-2" />
                        Factuur downloaden
                    </Button>
                </a>
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

                    <!-- Notes -->
                    <div v-if="order.notes" class="rounded-lg border p-6">
                        <h2 class="text-lg font-semibold mb-2">Opmerkingen</h2>
                        <p class="text-muted-foreground">{{ order.notes }}</p>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="space-y-6">
                    <!-- Status -->
                    <div class="rounded-lg border p-6">
                        <h2 class="text-lg font-semibold mb-4">Status</h2>
                        <Badge :variant="getStatusVariant(order.status)" class="text-sm">
                            {{ order.status_label }}
                        </Badge>
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
