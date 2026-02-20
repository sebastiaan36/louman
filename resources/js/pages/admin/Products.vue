<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface Product {
    id: number;
    title: string;
    category: string | null;
    price_groothandel: string;
    price_broodjeszaak: string;
    price_horeca: string;
    article_number: string;
    in_stock: boolean;
    photo_url: string | null;
    is_active: boolean;
}

defineProps<{
    products: Product[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Producten',
    },
];

const deleteProduct = (id: number) => {
    if (confirm('Weet je zeker dat je dit product wilt verwijderen?')) {
        router.delete(`/admin/products/${id}`, {
            preserveScroll: true,
        });
    }
};

const formatPrice = (price: string) => {
    return new Intl.NumberFormat('nl-NL', {
        style: 'currency',
        currency: 'EUR',
    }).format(parseFloat(price));
};
</script>

<template>
    <Head title="Producten" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Producten</h1>
                    <p class="text-sm text-muted-foreground">
                        Beheer productcatalogus
                    </p>
                </div>

                <Link href="/admin/products/create">
                    <Button>Product toevoegen</Button>
                </Link>
            </div>

            <div v-if="products.length === 0" class="rounded-lg border border-dashed p-12 text-center">
                <p class="text-muted-foreground">Geen producten gevonden</p>
                <p class="text-sm text-muted-foreground mt-2">
                    Klik op "Product toevoegen" om te beginnen
                </p>
            </div>

            <div v-else class="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-20">Foto</TableHead>
                            <TableHead>Naam</TableHead>
                            <TableHead>Categorie</TableHead>
                            <TableHead>Prijs</TableHead>
                            <TableHead>Voorraad</TableHead>
                            <TableHead>Artikelnr</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Acties</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="product in products" :key="product.id">
                            <TableCell>
                                <img
                                    v-if="product.photo_url"
                                    :src="product.photo_url"
                                    :alt="product.title"
                                    class="h-12 w-12 rounded object-cover"
                                />
                                <div
                                    v-else
                                    class="h-12 w-12 rounded bg-muted flex items-center justify-center text-muted-foreground text-xs"
                                >
                                    Geen foto
                                </div>
                            </TableCell>
                            <TableCell class="font-medium">{{ product.title }}</TableCell>
                            <TableCell>{{ product.category || '-' }}</TableCell>
                            <TableCell>
                                <div class="space-y-0.5 text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="text-muted-foreground text-xs">GH:</span>
                                        <span class="font-medium">{{ formatPrice(product.price_groothandel) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-muted-foreground text-xs">BZ:</span>
                                        <span class="font-medium">{{ formatPrice(product.price_broodjeszaak) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-muted-foreground text-xs">HC:</span>
                                        <span class="font-medium">{{ formatPrice(product.price_horeca) }}</span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="product.in_stock ? 'default' : 'destructive'">
                                    {{ product.in_stock ? 'Op voorraad' : 'Uitverkocht' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="font-mono text-sm">{{ product.article_number }}</TableCell>
                            <TableCell>
                                <Badge :variant="product.is_active ? 'default' : 'secondary'">
                                    {{ product.is_active ? 'Actief' : 'Inactief' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex gap-2 justify-end">
                                    <Link :href="`/admin/products/${product.id}/edit`">
                                        <Button size="sm" variant="outline">Bewerken</Button>
                                    </Link>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        @click="deleteProduct(product.id)"
                                    >
                                        Verwijderen
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
