<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface Product {
    id: number;
    title: string;
    category: string | null;
    price: string;
    article_number: string;
    in_stock: boolean;
    photo_url: string | null;
    is_active: boolean;
}

interface Filters {
    search: string | null;
    sort: string | null;
}

const props = defineProps<{
    products: Product[];
    filters: Filters;
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

const searchQuery = ref(props.filters.search || '');
const selectedSort = ref(props.filters.sort || 'newest');

const sortOptions = [
    { value: 'newest', label: 'Nieuwste eerst' },
    { value: 'article_asc', label: 'Artikelnr. ↑ (laag → hoog)' },
    { value: 'article_desc', label: 'Artikelnr. ↓ (hoog → laag)' },
    { value: 'price_asc', label: 'Prijs ↑ (laag → hoog)' },
    { value: 'price_desc', label: 'Prijs ↓ (hoog → laag)' },
    { value: 'popularity', label: 'Populariteit' },
];

watch([searchQuery, selectedSort], ([search, sort]) => {
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (sort && sort !== 'newest') params.set('sort', sort);

    router.get(`/admin/products?${params.toString()}`, {}, {
        preserveState: true,
        preserveScroll: true,
    });
}, { deep: true });

const page = usePage();
const importResults = computed(
    () =>
        (page.props.flash as { import_results?: { imported: number; updated: number; skipped: string[] } })
            ?.import_results,
);

const importOpen = ref(false);
const csvFile = ref<File | null>(null);
const importing = ref(false);

const handleCsvChange = (e: Event) => {
    csvFile.value = (e.target as HTMLInputElement).files?.[0] ?? null;
};

const submitImport = () => {
    if (!csvFile.value) return;
    importing.value = true;
    const formData = new FormData();
    formData.append('csv_file', csvFile.value);
    router.post('/admin/products/import', formData, {
        onFinish: () => {
            importing.value = false;
            importOpen.value = false;
            csvFile.value = null;
        },
    });
};

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

                <div class="flex gap-2">
                    <a href="/admin/products/export">
                        <Button variant="outline">CSV downloaden</Button>
                    </a>
                    <Button variant="outline" @click="importOpen = true">CSV importeren</Button>
                    <Link href="/admin/products/create">
                        <Button>Product toevoegen</Button>
                    </Link>
                </div>
            </div>

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
                                placeholder="Zoek op naam, artikelnummer..."
                                class="pl-8"
                            />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label>Sorteren</Label>
                        <Select v-model="selectedSort">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in sortOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-end">
                        <Button
                            variant="outline"
                            @click="searchQuery = ''; selectedSort = 'newest'"
                            :disabled="!searchQuery && selectedSort === 'newest'"
                        >
                            Filters wissen
                        </Button>
                    </div>
                </div>
            </div>

            <div
                v-if="importResults?.skipped?.length"
                class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm"
            >
                <p class="mb-2 font-medium text-yellow-800">
                    Overgeslagen rijen ({{ importResults.skipped.length }}):
                </p>
                <ul class="list-inside list-disc space-y-1 text-yellow-700">
                    <li v-for="reason in importResults.skipped" :key="reason">{{ reason }}</li>
                </ul>
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
                                <span class="font-medium">{{ formatPrice(product.price) }}</span>
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

    <Dialog :open="importOpen" @update:open="importOpen = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Producten importeren via CSV</DialogTitle>
                <DialogDescription>
                    Upload een CSV-bestand met productgegevens. Rijen zonder naam of artikelnummer worden overgeslagen.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <Label>CSV-bestand</Label>
                    <input
                        type="file"
                        accept=".csv,.txt"
                        class="mt-1 block w-full text-sm"
                        @change="handleCsvChange"
                    />
                </div>
                <div class="rounded-md bg-muted p-3 text-xs text-muted-foreground space-y-1">
                    <p class="font-medium">Verwachte kolomnamen (eerste rij):</p>
                    <p class="font-mono break-all leading-relaxed">
                        article_number, title, description, price, category_id, weight,
                        in_stock, is_active, ingredients, allergens, nutrition_energy, nutrition_fat,
                        nutrition_saturated_fat, nutrition_carbohydrates, nutrition_sugars,
                        nutrition_protein, nutrition_salt, nutrition_fiber
                    </p>
                    <p class="mt-2">Gebruik <code class="font-mono">.</code> als decimaalteken voor prijzen. Lege kolommen krijgen standaardwaarden.</p>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="importOpen = false">Annuleren</Button>
                <Button :disabled="!csvFile || importing" @click="submitImport">
                    {{ importing ? 'Importeren...' : 'Importeren' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
