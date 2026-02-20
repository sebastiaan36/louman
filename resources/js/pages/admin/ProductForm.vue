<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { ref, computed } from 'vue';
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

interface Category {
    id: number;
    name: string;
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
    title: string;
    price: string;
    description: string;
    ingredients: string;
    allergens: string;
    nutrition_facts?: NutritionFacts;
    weight: string | null;
    article_number: string;
    in_stock: boolean;
    photo_url: string | null;
    is_active: boolean;
}

const props = defineProps<{
    product?: Product;
    categories: Category[];
    errors?: Record<string, string>;
}>();

const isEdit = computed(() => !!props.product);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Producten',
        href: '/admin/products',
    },
    {
        title: isEdit.value ? 'Product bewerken' : 'Product toevoegen',
    },
];

const form = ref({
    category_id: props.product?.category_id?.toString() || '',
    title: props.product?.title || '',
    price_groothandel: props.product?.price_groothandel || '',
    price_broodjeszaak: props.product?.price_broodjeszaak || '',
    price_horeca: props.product?.price_horeca || '',
    description: props.product?.description || '',
    ingredients: props.product?.ingredients || '',
    allergens: props.product?.allergens || '',
    weight: props.product?.weight || '',
    article_number: props.product?.article_number || '',
    in_stock: props.product?.in_stock ?? true,
    is_active: props.product?.is_active ?? true,
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
    formData.append('title', form.value.title);
    formData.append('price_groothandel', form.value.price_groothandel);
    formData.append('price_broodjeszaak', form.value.price_broodjeszaak);
    formData.append('price_horeca', form.value.price_horeca);
    formData.append('description', form.value.description);
    formData.append('ingredients', form.value.ingredients);
    formData.append('allergens', form.value.allergens);
    if (form.value.weight) {
        formData.append('weight', form.value.weight);
    }
    formData.append('article_number', form.value.article_number);
    formData.append('in_stock', form.value.in_stock ? '1' : '0');
    formData.append('is_active', form.value.is_active ? '1' : '0');

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

    const url = isEdit.value
        ? `/admin/products/${props.product?.id}`
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
    router.visit('/admin/products');
};
</script>

<template>
    <Head :title="isEdit ? 'Product bewerken' : 'Product toevoegen'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
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
                            <Label for="price_groothandel">Prijs Groothandel (€) <span class="text-destructive">*</span></Label>
                            <Input
                                id="price_groothandel"
                                v-model="form.price_groothandel"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                placeholder="0.00"
                            />
                            <InputError :message="errors?.price_groothandel" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="price_broodjeszaak">Prijs Broodjeszaak (€) <span class="text-destructive">*</span></Label>
                            <Input
                                id="price_broodjeszaak"
                                v-model="form.price_broodjeszaak"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                placeholder="0.00"
                            />
                            <InputError :message="errors?.price_broodjeszaak" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="price_horeca">Prijs Horeca (€) <span class="text-destructive">*</span></Label>
                            <Input
                                id="price_horeca"
                                v-model="form.price_horeca"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                placeholder="0.00"
                            />
                            <InputError :message="errors?.price_horeca" />
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
                            :checked="form.is_active"
                            @update:checked="form.is_active = $event"
                        />
                        <Label for="is_active" class="cursor-pointer">
                            Product is actief (zichtbaar voor klanten)
                        </Label>
                    </div>
                    <InputError :message="errors?.is_active" />
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
