<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface Subcategory {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
    children: Subcategory[];
}

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
    category_id: number | null;
    subcategory_id: number | null;
    title: string;
    price: string;
    price_per_kg: string | null;
    suggested_retail_price: string | null;
    description: string;
    ingredients: string;
    allergens: string;
    nutrition_facts?: NutritionFacts;
    weight: string | null;
    article_number: string;
    in_stock: boolean;
    photo_url: string | null;
    is_active: boolean;
    is_private_label: boolean;
    visible_customer_ids: number[];
}

interface CustomerOption {
    id: number;
    company_name: string;
}

const props = defineProps<{
    product?: Product;
    categories: Category[];
    customers: CustomerOption[];
    errors?: Record<string, string>;
    filters?: { search?: string | null; sort?: string | null; private_label?: string | boolean | null };
}>();

const isEdit = computed(() => !!props.product);

// The list's active sort/search/filter as a query string, so saving or
// cancelling returns to the same list view.
const listQuery = computed(() => {
    const params = new URLSearchParams();
    if (props.filters?.search) params.set('search', props.filters.search);
    if (props.filters?.sort) params.set('sort', props.filters.sort);
    if (props.filters?.private_label) params.set('private_label', '1');
    const query = params.toString();
    return query ? `?${query}` : '';
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Producten',
        href: `/admin/products${listQuery.value}`,
    },
    {
        title: isEdit.value ? 'Product bewerken' : 'Product toevoegen',
    },
];

const form = ref({
    category_id: props.product?.category_id?.toString() || '',
    subcategory_id: props.product?.subcategory_id?.toString() || 'none',
    title: props.product?.title || '',
    price: props.product?.price || '',
    price_per_kg: props.product?.price_per_kg || '',
    suggested_retail_price: props.product?.suggested_retail_price || '',
    description: props.product?.description || '',
    ingredients: props.product?.ingredients || '',
    allergens: props.product?.allergens || '',
    weight: props.product?.weight || '',
    article_number: props.product?.article_number || '',
    in_stock: props.product?.in_stock ?? true,
    is_active: props.product?.is_active ?? true,
    is_private_label: props.product?.is_private_label ?? false,
    visible_customer_ids: props.product?.visible_customer_ids ?? [],
    nutrition_facts: {
        energy: props.product?.nutrition_facts?.energy || '',
        fat: props.product?.nutrition_facts?.fat || '',
        saturated_fat: props.product?.nutrition_facts?.saturated_fat || '',
        carbohydrates: props.product?.nutrition_facts?.carbohydrates || '',
        sugars: props.product?.nutrition_facts?.sugars || '',
        protein: props.product?.nutrition_facts?.protein || '',
        salt: props.product?.nutrition_facts?.salt || '',
        fiber: props.product?.nutrition_facts?.fiber || '',
    },
});

const selectedCategoryChildren = computed(() => {
    const cat = props.categories.find((c) => c.id.toString() === form.value.category_id);
    return cat?.children ?? [];
});

watch(() => form.value.category_id, () => {
    form.value.subcategory_id = 'none';
});

// Parse the free-text weight field to kilograms, e.g. "1500 gram", "500 g",
// "1,5 kg", "per kg". Returns null when there is no usable numeric weight.
const weightInKg = (): number | null => {
    const raw = (form.value.weight || '').toString().toLowerCase();

    const kilo = raw.match(/([\d.,]+)\s*(kilo|kg)\b/);
    if (kilo) {
        return parseFloat(kilo[1].replace(',', '.'));
    }

    const gram = raw.match(/([\d.,]+)\s*(gram|gr|g)\b/);
    if (gram) {
        return parseFloat(gram[1].replace(',', '.')) / 1000;
    }

    if (/per\s*kg|\bkg\b/.test(raw)) {
        return 1;
    }

    return null;
};

const toMoney = (value: number): string => (Math.round(value * 100) / 100).toFixed(2);

// Entering a unit price fills in the price per kg (and vice versa), based on
// the product weight. The value is read from the event so we only ever update
// the other field — no two-way watcher loop.
const syncPricePerKgFromPrice = (event: Event) => {
    const kg = weightInKg();
    const price = parseFloat((event.target as HTMLInputElement).value.replace(',', '.'));
    if (kg && kg > 0 && !Number.isNaN(price)) {
        form.value.price_per_kg = toMoney(price / kg);
    }
};

const syncPriceFromPricePerKg = (event: Event) => {
    const kg = weightInKg();
    const perKg = parseFloat((event.target as HTMLInputElement).value.replace(',', '.'));
    if (kg && kg > 0 && !Number.isNaN(perKg)) {
        form.value.price = toMoney(perKg * kg);
    }
};

const toggleVisibleCustomer = (id: number, checked: boolean | 'indeterminate') => {
    if (checked === true) {
        if (!form.value.visible_customer_ids.includes(id)) {
            form.value.visible_customer_ids.push(id);
        }
    } else {
        form.value.visible_customer_ids = form.value.visible_customer_ids.filter((x) => x !== id);
    }
};

const customerSearch = ref('');

const filteredCustomers = computed(() => {
    const q = customerSearch.value.trim().toLowerCase();
    if (!q) return props.customers;
    return props.customers.filter((c) => c.company_name.toLowerCase().includes(q));
});

const photoFile = ref<File | null>(null);
const photoPreview = ref<string | null>(props.product?.photo_url || null);
const processing = ref(false);

const handlePhotoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        photoFile.value = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removePhoto = () => {
    photoFile.value = null;
    photoPreview.value = props.product?.photo_url || null;
    const input = document.getElementById('photo') as HTMLInputElement;
    if (input) {
        input.value = '';
    }
};

const submit = () => {
    processing.value = true;

    const formData = new FormData();

    // Append all form fields
    if (form.value.category_id) {
        formData.append('category_id', form.value.category_id);
    }
    if (form.value.subcategory_id && form.value.subcategory_id !== 'none') {
        formData.append('subcategory_id', form.value.subcategory_id);
    }
    formData.append('title', form.value.title);
    formData.append('price', form.value.price);
    if (form.value.price_per_kg) {
        formData.append('price_per_kg', form.value.price_per_kg);
    }
    if (form.value.suggested_retail_price) {
        formData.append('suggested_retail_price', form.value.suggested_retail_price);
    }
    formData.append('description', form.value.description);
    formData.append('ingredients', form.value.ingredients);
    formData.append('allergens', form.value.allergens);
    if (form.value.weight) {
        formData.append('weight', form.value.weight);
    }
    formData.append('article_number', form.value.article_number);
    formData.append('in_stock', form.value.in_stock ? '1' : '0');
    formData.append('is_active', form.value.is_active ? '1' : '0');
    formData.append('is_private_label', form.value.is_private_label ? '1' : '0');
    if (form.value.is_private_label) {
        form.value.visible_customer_ids.forEach((id) => {
            formData.append('visible_customer_ids[]', id.toString());
        });
    }

    // Append nutrition facts
    if (form.value.nutrition_facts.energy) {
        formData.append('nutrition_facts[energy]', form.value.nutrition_facts.energy);
    }
    if (form.value.nutrition_facts.fat) {
        formData.append('nutrition_facts[fat]', form.value.nutrition_facts.fat);
    }
    if (form.value.nutrition_facts.saturated_fat) {
        formData.append('nutrition_facts[saturated_fat]', form.value.nutrition_facts.saturated_fat);
    }
    if (form.value.nutrition_facts.carbohydrates) {
        formData.append('nutrition_facts[carbohydrates]', form.value.nutrition_facts.carbohydrates);
    }
    if (form.value.nutrition_facts.sugars) {
        formData.append('nutrition_facts[sugars]', form.value.nutrition_facts.sugars);
    }
    if (form.value.nutrition_facts.protein) {
        formData.append('nutrition_facts[protein]', form.value.nutrition_facts.protein);
    }
    if (form.value.nutrition_facts.salt) {
        formData.append('nutrition_facts[salt]', form.value.nutrition_facts.salt);
    }
    if (form.value.nutrition_facts.fiber) {
        formData.append('nutrition_facts[fiber]', form.value.nutrition_facts.fiber);
    }

    // Append photo if selected
    if (photoFile.value) {
        formData.append('photo', photoFile.value);
    }

    // Keep the list filters on the update URL so the redirect returns to the
    // same sort/search view.
    const url = isEdit.value
        ? `/admin/products/${props.product?.id}${listQuery.value}`
        : '/admin/products';

    // For Laravel, we need to add _method for PUT/PATCH
    if (isEdit.value) {
        formData.append('_method', 'PUT');
    }

    router.post(url, formData, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
};

const cancel = () => {
    router.visit(`/admin/products${listQuery.value}`);
};
</script>

<template>
    <Head :title="isEdit ? 'Product bewerken' : 'Product toevoegen'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ isEdit ? 'Product bewerken' : 'Product toevoegen' }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ isEdit ? 'Wijzig de productgegevens' : 'Voeg een nieuw product toe aan de catalogus' }}
                </p>
            </div>

            <form @submit.prevent="submit" class="max-w-3xl space-y-6">
                <!-- Basis informatie -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Basisinformatie</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="title">Naam <span class="text-destructive">*</span></Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="Bijv. Rundergehakt"
                            />
                            <InputError :message="errors?.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="category_id">Categorie</Label>
                            <Select v-model="form.category_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecteer categorie (optioneel)" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id.toString()"
                                    >
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors?.category_id" />
                        </div>

                        <div v-if="selectedCategoryChildren.length > 0" class="grid gap-2">
                            <Label for="subcategory_id">Subcategorie (optioneel)</Label>
                            <Select v-model="form.subcategory_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecteer subcategorie (optioneel)" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">— Geen subcategorie —</SelectItem>
                                    <SelectItem
                                        v-for="child in selectedCategoryChildren"
                                        :key="child.id"
                                        :value="child.id.toString()"
                                    >
                                        {{ child.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors?.subcategory_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="article_number">Artikelnummer <span class="text-destructive">*</span></Label>
                            <Input
                                id="article_number"
                                v-model="form.article_number"
                                type="text"
                                required
                                placeholder="Bijv. RG-001"
                            />
                            <InputError :message="errors?.article_number" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="weight">Gewicht</Label>
                            <Input
                                id="weight"
                                v-model="form.weight"
                                type="text"
                                placeholder="Bijv. 500g, 1kg"
                            />
                            <InputError :message="errors?.weight" />
                        </div>
                    </div>
                </div>

                <!-- Productfoto -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Productfoto</h3>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="photo">
                                Foto {{ isEdit ? '' : '(verplicht)' }}
                                <span v-if="!isEdit" class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="photo"
                                type="file"
                                accept="image/jpeg,image/png,image/jpg,image/webp"
                                @change="handlePhotoChange"
                                :required="!isEdit && !photoPreview"
                            />
                            <p class="text-xs text-muted-foreground">
                                Toegestaan: JPEG, PNG, JPG, WEBP. Max 5MB.
                            </p>
                            <InputError :message="errors?.photo" />
                        </div>

                        <div v-if="photoPreview" class="relative w-48">
                            <img
                                :src="photoPreview"
                                alt="Preview"
                                class="rounded-lg border object-cover w-48 h-48"
                            />
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                class="absolute top-2 right-2"
                                @click="removePhoto"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Prijs en voorraad -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Prijs en voorraad</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="price">Prijs (€) <span class="text-destructive">*</span></Label>
                            <Input
                                id="price"
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                placeholder="0.00"
                                @input="syncPricePerKgFromPrice"
                            />
                            <InputError :message="errors?.price" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="price_per_kg">Prijs per kg (€)</Label>
                            <Input
                                id="price_per_kg"
                                v-model="form.price_per_kg"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="optioneel"
                                @input="syncPriceFromPricePerKg"
                            />
                            <InputError :message="errors?.price_per_kg" />
                        </div>

                        <p class="-mt-2 text-xs text-muted-foreground sm:col-span-2">
                            Prijs en prijs per kg worden automatisch uit elkaar berekend op basis van het gewicht.
                        </p>

                        <div class="grid gap-2">
                            <Label for="suggested_retail_price">Verkoopadviesprijs (€)</Label>
                            <Input
                                id="suggested_retail_price"
                                v-model="form.suggested_retail_price"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                            />
                            <InputError :message="errors?.suggested_retail_price" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="in_stock">Voorraad <span class="text-destructive">*</span></Label>
                            <Select v-model="form.in_stock">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecteer voorraad status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="true">Op voorraad</SelectItem>
                                    <SelectItem :value="false">Uitverkocht</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors?.in_stock" />
                        </div>
                    </div>
                </div>

                <!-- Beschrijving -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Beschrijving</h3>

                    <div class="grid gap-2">
                        <Label for="description">Omschrijving <span class="text-destructive">*</span></Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            required
                            placeholder="Beschrijf het product..."
                            rows="5"
                        />
                        <InputError :message="errors?.description" />
                    </div>
                </div>

                <!-- Ingrediënten en allergenen -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Ingrediënten en allergenen</h3>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="ingredients">Ingrediënten</Label>
                            <Textarea
                                id="ingredients"
                                v-model="form.ingredients"
                                placeholder="Gescheiden door komma's, bijv: varkensvlees, zout, peper"
                                rows="3"
                            />
                            <p class="text-xs text-muted-foreground">
                                Gescheiden door komma's
                            </p>
                            <InputError :message="errors?.ingredients" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="allergens">Allergenen</Label>
                            <Textarea
                                id="allergens"
                                v-model="form.allergens"
                                placeholder="Gescheiden door komma's, bijv: melk, gluten, noten"
                                rows="3"
                            />
                            <p class="text-xs text-muted-foreground">
                                Gescheiden door komma's
                            </p>
                            <InputError :message="errors?.allergens" />
                        </div>
                    </div>
                </div>

                <!-- Voedingswaarde -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Voedingswaarde</h3>
                    <p class="text-xs text-muted-foreground">
                        Voedingswaarde per 100 gram (optioneel)
                    </p>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="energy">Energie (kcal)</Label>
                            <Input
                                id="energy"
                                v-model="form.nutrition_facts.energy"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0"
                            />
                            <InputError :message="errors?.['nutrition_facts.energy']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="fat">Vet (g)</Label>
                            <Input
                                id="fat"
                                v-model="form.nutrition_facts.fat"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0"
                            />
                            <InputError :message="errors?.['nutrition_facts.fat']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="saturated_fat">Waarvan verzadigd (g)</Label>
                            <Input
                                id="saturated_fat"
                                v-model="form.nutrition_facts.saturated_fat"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0"
                            />
                            <InputError :message="errors?.['nutrition_facts.saturated_fat']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="carbohydrates">Koolhydraten (g)</Label>
                            <Input
                                id="carbohydrates"
                                v-model="form.nutrition_facts.carbohydrates"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0"
                            />
                            <InputError :message="errors?.['nutrition_facts.carbohydrates']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="sugars">Waarvan suikers (g)</Label>
                            <Input
                                id="sugars"
                                v-model="form.nutrition_facts.sugars"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0"
                            />
                            <InputError :message="errors?.['nutrition_facts.sugars']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="protein">Eiwitten (g)</Label>
                            <Input
                                id="protein"
                                v-model="form.nutrition_facts.protein"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0"
                            />
                            <InputError :message="errors?.['nutrition_facts.protein']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="salt">Zout (g)</Label>
                            <Input
                                id="salt"
                                v-model="form.nutrition_facts.salt"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0"
                            />
                            <InputError :message="errors?.['nutrition_facts.salt']" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="fiber">Vezel (g)</Label>
                            <Input
                                id="fiber"
                                v-model="form.nutrition_facts.fiber"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0"
                            />
                            <InputError :message="errors?.['nutrition_facts.fiber']" />
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Status</h3>

                    <div class="flex items-center space-x-2">
                        <Checkbox
                            id="is_active"
                            v-model="form.is_active"
                        />
                        <Label for="is_active" class="cursor-pointer">
                            Product is actief (zichtbaar voor klanten)
                        </Label>
                    </div>
                    <InputError :message="errors?.is_active" />
                </div>

                <!-- Private label -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Private label</h3>

                    <div class="flex items-center space-x-2">
                        <Checkbox id="is_private_label" v-model="form.is_private_label" />
                        <Label for="is_private_label" class="cursor-pointer">
                            Private label product (alleen zichtbaar voor geselecteerde klanten)
                        </Label>
                    </div>

                    <div v-if="form.is_private_label" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label>Zichtbaar voor klanten</Label>
                            <span class="text-xs text-muted-foreground">
                                {{ form.visible_customer_ids.length }} geselecteerd
                            </span>
                        </div>
                        <p v-if="customers.length === 0" class="text-sm text-muted-foreground">
                            Er zijn nog geen goedgekeurde klanten.
                        </p>
                        <template v-else>
                            <p v-if="form.visible_customer_ids.length === 0" class="text-xs text-destructive">
                                Zonder gekoppelde klanten is dit product voor niemand zichtbaar.
                            </p>
                            <Input
                                v-model="customerSearch"
                                type="search"
                                placeholder="Zoek klant op bedrijfsnaam..."
                            />
                            <div class="max-h-64 overflow-y-auto rounded-md border divide-y">
                                <label
                                    v-for="customer in filteredCustomers"
                                    :key="customer.id"
                                    class="flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-muted"
                                >
                                    <Checkbox
                                        :model-value="form.visible_customer_ids.includes(customer.id)"
                                        @update:model-value="(checked) => toggleVisibleCustomer(customer.id, checked)"
                                    />
                                    <span class="text-sm">{{ customer.company_name }}</span>
                                </label>
                                <p v-if="filteredCustomers.length === 0" class="px-3 py-2 text-sm text-muted-foreground">
                                    Geen klanten gevonden voor "{{ customerSearch }}".
                                </p>
                            </div>
                        </template>
                    </div>
                    <InputError :message="errors?.is_private_label" />
                </div>

                <!-- Acties -->
                <div class="flex gap-2">
                    <Button type="submit" :disabled="processing">
                        {{ isEdit ? 'Opslaan' : 'Product toevoegen' }}
                    </Button>
                    <Button type="button" variant="outline" @click="cancel" :disabled="processing">
                        Annuleren
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
