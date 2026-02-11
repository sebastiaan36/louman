<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { FileText, Download, ShoppingCart, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Order {
    id: number;
    order_number: string;
    created_at: string;
    total: string;
    status: string;
    status_label: string;
    item_count: number;
}

interface Filters {
    search: string | null;
}

const props = defineProps<{
    orders: Order[];
    filters: Filters;
}>();

const searchQuery = ref(props.filters.search || '');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Mijn Bestellingen',
    },
];

const formatPrice = (price: string) => {
    return new Intl.NumberFormat('nl-NL', {
        style: 'currency',
        currency: 'EUR',
    }).format(parseFloat(price));
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

const reorder = (orderId: number) => {
    router.post(`/customer/orders/${orderId}/reorder`, {}, {
        preserveScroll: true,
    });
};

// Update URL params with current search
const updateFilters = () => {
    const params = new URLSearchParams();
    if (searchQuery.value) params.set('search', searchQuery.value);

    router.get(`/customer/orders?${params.toString()}`, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch for search changes
watch(searchQuery, () => updateFilters());

const clearSearch = () => {
    searchQuery.value = '';
};
</script>

<template>
    <Head title="Mijn Bestellingen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <FileText class="h-6 w-6" />
                    Mijn Bestellingen
                </h1>
                <p class="text-sm text-muted-foreground">
                    Bekijk je bestelgeschiedenis
                </p>
            </div>

            <!-- Search -->
            <div class="rounded-lg border p-4">
                <div class="grid gap-2 md:w-1/3">
                    <Label for="search">Zoeken</Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="search"
                            v-model="searchQuery"
                            placeholder="Bestelnummer (bijv. #123)..."
                            class="pl-9"
                        />
                    </div>
                    <Button
                        v-if="searchQuery"
                        variant="outline"
                        size="sm"
                        @click="clearSearch"
                        class="w-fit"
                    >
                        Wis zoekopdracht
                    </Button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="orders.length === 0" class="rounded-lg border border-dashed p-12 text-center space-y-4">
                <FileText class="h-16 w-16 mx-auto text-muted-foreground" />
                <div>
                    <p class="text-lg font-medium text-muted-foreground">Geen bestellingen</p>
                    <p class="text-sm text-muted-foreground mt-2">
                        {{ searchQuery ? 'Geen bestellingen gevonden met deze zoekopdracht' : 'Je hebt nog geen bestellingen geplaatst' }}
                    </p>
                </div>
                <Link v-if="!searchQuery" href="/customer/products">
                    <Button>
                        Blader door producten
                    </Button>
                </Link>
            </div>

            <!-- Orders Table -->
            <div v-else class="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Bestelnummer</TableHead>
                            <TableHead>Datum</TableHead>
                            <TableHead>Totaal</TableHead>
                            <TableHead>Producten</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Acties</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="order in orders" :key="order.id">
                            <TableCell class="font-medium">{{ order.order_number }}</TableCell>
                            <TableCell>{{ order.created_at }}</TableCell>
                            <TableCell class="font-medium">{{ formatPrice(order.total) }}</TableCell>
                            <TableCell>{{ order.item_count }} {{ order.item_count === 1 ? 'product' : 'producten' }}</TableCell>
                            <TableCell>
                                <Badge :variant="getStatusVariant(order.status)">
                                    {{ order.status_label }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex gap-2 justify-end">
                                    <Link :href="`/customer/orders/${order.id}`">
                                        <Button size="sm" variant="outline">Details</Button>
                                    </Link>
                                    <a :href="`/customer/orders/${order.id}/packing-slip`" target="_blank">
                                        <Button size="sm" variant="outline">
                                            <Download class="h-4 w-4 mr-1" />
                                            Pakbon
                                        </Button>
                                    </a>
                                    <Button size="sm" variant="outline" @click="reorder(order.id)">
                                        <ShoppingCart class="h-4 w-4 mr-1" />
                                        Opnieuw bestellen
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
