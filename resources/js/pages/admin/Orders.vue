<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
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
import Pagination from '@/components/Pagination.vue';
import { ShoppingCart, Download, CheckSquare, FileArchive, Search, Plus, ClipboardList, LayoutGrid } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';

const page = usePage();

interface Order {
    id: number;
    order_number: string;
    customer_name: string;
    customer_delivery_day: string | null;
    created_at: string;
    total: string;
    status: string;
    status_label: string;
    item_count: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedOrders {
    data: Order[];
    links: PaginationLink[];
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
    first_page_url: string;
    last_page_url: string;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
}

interface Filters {
    status: string | null;
    search: string | null;
}

const props = defineProps<{
    orders: PaginatedOrders;
    filters: Filters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Bestellingen',
    },
];

const selectedStatus = ref(props.filters.status || 'all');
const searchQuery = ref(props.filters.search || '');
const selectedOrders = ref<number[]>([]);
const bulkAction = ref<string>('');
const bulkStatusValue = ref<string>('');

// Update URL params with current filters
const updateFilters = () => {
    const params = new URLSearchParams();
    if (selectedStatus.value && selectedStatus.value !== 'all') params.set('status', selectedStatus.value);
    if (searchQuery.value) params.set('search', searchQuery.value);

    router.get(`/admin/orders?${params.toString()}`, {}, {
        preserveState: true,
        preserveScroll: true,
    });
    // Clear selections when filters change
    selectedOrders.value = [];
};

// Watch for filter changes
watch(selectedStatus, () => updateFilters());
watch(searchQuery, () => updateFilters());

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

const clearFilters = () => {
    selectedStatus.value = 'all';
    searchQuery.value = '';
};

// Bulk selection
const allSelected = computed(() => {
    return props.orders.data.length > 0 && selectedOrders.value.length === props.orders.data.length;
});

const toggleAllSelected = (checked: boolean | 'indeterminate') => {
    if (checked === true) {
        selectedOrders.value = props.orders.data.map(o => o.id);
    } else {
        selectedOrders.value = [];
    }
};

const toggleOrderSelection = (orderId: number, checked: boolean | 'indeterminate') => {
    const index = selectedOrders.value.indexOf(orderId);
    if (checked === true && index === -1) {
        selectedOrders.value.push(orderId);
    } else if (checked === false && index !== -1) {
        selectedOrders.value.splice(index, 1);
    }
};

const isOrderSelected = (orderId: number) => {
    return selectedOrders.value.includes(orderId);
};

// Bulk actions
const executeBulkStatusUpdate = () => {
    if (selectedOrders.value.length === 0 || !bulkStatusValue.value) {
        return;
    }

    if (!confirm(`Weet je zeker dat je ${selectedOrders.value.length} ${selectedOrders.value.length === 1 ? 'bestelling' : 'bestellingen'} wilt bijwerken?`)) {
        return;
    }

    router.post('/admin/orders/bulk/update-status', {
        order_ids: selectedOrders.value,
        status: bulkStatusValue.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedOrders.value = [];
            bulkStatusValue.value = '';
        },
    });
};

const downloadBulkPackingSlips = () => {
    if (selectedOrders.value.length === 0) {
        return;
    }

    // Create and submit a native form for file download
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/orders/bulk/download-packing-slips';
    form.style.display = 'none';

    // Add CSRF token from Inertia
    const csrfToken = page.props.csrf_token as string ||
                      document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);

    // Add order IDs
    selectedOrders.value.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'order_ids[]';
        input.value = id.toString();
        form.appendChild(input);
    });

    // Submit the form
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};
</script>

<template>
    <Head title="Bestellingen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <ShoppingCart class="h-6 w-6" />
                        Bestellingen
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Beheer alle klantbestellingen
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="/admin/orders/production-list" target="_blank">
                        <Button variant="outline">
                            <ClipboardList class="h-4 w-4 mr-2" />
                            Productielijst
                        </Button>
                    </a>
                    <a href="/admin/orders/customer-overview" target="_blank">
                        <Button variant="outline">
                            <LayoutGrid class="h-4 w-4 mr-2" />
                            Bestellingenoverzicht
                        </Button>
                    </a>
                    <Link href="/admin/orders/create">
                        <Button>
                            <Plus class="h-4 w-4 mr-2" />
                            Nieuwe bestelling
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-lg border p-4">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="grid gap-2">
                        <Label for="search">Zoeken</Label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="search"
                                v-model="searchQuery"
                                placeholder="Bestelnummer of klant..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <Select v-model="selectedStatus">
                            <SelectTrigger>
                                <SelectValue placeholder="Alle statussen" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Alle bestellingen</SelectItem>
                                <SelectItem value="pending">In behandeling</SelectItem>
                                <SelectItem value="confirmed">Bevestigd</SelectItem>
                                <SelectItem value="completed">Voltooid</SelectItem>
                                <SelectItem value="cancelled">Geannuleerd</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-end">
                        <Button
                            variant="outline"
                            @click="clearFilters"
                            :disabled="selectedStatus === 'all' && !searchQuery"
                        >
                            Filters wissen
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions Toolbar -->
            <div v-if="selectedOrders.length > 0" class="rounded-lg border bg-muted/50 p-4">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div class="text-sm font-medium">
                        {{ selectedOrders.length }} {{ selectedOrders.length === 1 ? 'bestelling' : 'bestellingen' }} geselecteerd
                    </div>
                    <div class="flex flex-col gap-3 md:flex-row md:items-end">
                        <div class="grid gap-2">
                            <Label for="bulkStatus">Status wijzigen naar</Label>
                            <div class="flex gap-2">
                                <Select v-model="bulkStatusValue">
                                    <SelectTrigger class="w-[180px]">
                                        <SelectValue placeholder="Selecteer status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pending">In behandeling</SelectItem>
                                        <SelectItem value="confirmed">Bevestigd</SelectItem>
                                        <SelectItem value="completed">Voltooid</SelectItem>
                                        <SelectItem value="cancelled">Geannuleerd</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button
                                    @click="executeBulkStatusUpdate"
                                    :disabled="!bulkStatusValue"
                                >
                                    <CheckSquare class="h-4 w-4 mr-2" />
                                    Bijwerken
                                </Button>
                            </div>
                        </div>
                        <Button
                            variant="outline"
                            @click="downloadBulkPackingSlips"
                        >
                            <FileArchive class="h-4 w-4 mr-2" />
                            Download pakbonnen (ZIP)
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="orders.data.length === 0" class="rounded-lg border border-dashed p-12 text-center">
                <p class="text-muted-foreground">Geen bestellingen gevonden</p>
                <p class="text-sm text-muted-foreground mt-2">
                    {{ selectedStatus || searchQuery ? 'Probeer een andere zoekopdracht of filter' : 'Er zijn nog geen bestellingen' }}
                </p>
            </div>

            <!-- Orders Table -->
            <div v-else class="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-12">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="(e) => toggleAllSelected((e.target as HTMLInputElement).checked)"
                                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                />
                            </TableHead>
                            <TableHead>Bestelnummer</TableHead>
                            <TableHead>Klant</TableHead>
                            <TableHead>Datum</TableHead>
                            <TableHead>Totaal</TableHead>
                            <TableHead>Producten</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Acties</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="order in orders.data" :key="order.id">
                            <TableCell>
                                <input
                                    type="checkbox"
                                    :checked="isOrderSelected(order.id)"
                                    @change="(e) => toggleOrderSelection(order.id, (e.target as HTMLInputElement).checked)"
                                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                />
                            </TableCell>
                            <TableCell class="font-medium">{{ order.order_number }}</TableCell>
                            <TableCell>
                                <div class="flex items-center justify-between gap-4">
                                    <span>{{ order.customer_name }}</span>
                                    <span v-if="order.customer_delivery_day" class="text-xs text-muted-foreground capitalize shrink-0">
                                        {{ order.customer_delivery_day }}
                                    </span>
                                </div>
                            </TableCell>
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
                                    <Link :href="`/admin/orders/${order.id}`">
                                        <Button size="sm" variant="outline">Details</Button>
                                    </Link>
                                    <a :href="`/admin/orders/${order.id}/packing-slip`" target="_blank">
                                        <Button size="sm" variant="outline">
                                            <Download class="h-4 w-4 mr-1" />
                                            Pakbon
                                        </Button>
                                    </a>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <Pagination
                    v-if="orders.links && orders.last_page > 1"
                    :links="orders.links"
                    :current-page="orders.current_page"
                    :from="orders.from"
                    :to="orders.to"
                    :total="orders.total"
                    :last-page="orders.last_page"
                />
            </div>
        </div>
    </AppLayout>
</template>
