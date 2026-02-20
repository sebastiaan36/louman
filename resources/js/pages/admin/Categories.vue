<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface Category {
    id: number;
    name: string;
    description?: string;
    sort_order: number;
    products_count: number;
}

defineProps<{
    categories: Category[];
    errors?: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Categorieën',
    },
];

const dialogOpen = ref(false);
const editingCategory = ref<Category | null>(null);

const form = ref({
    name: '',
    description: '',
    sort_order: 0,
});

const openDialog = (category?: Category) => {
    if (category) {
        editingCategory.value = category;
        form.value = {
            name: category.name,
            description: category.description || '',
            sort_order: category.sort_order,
        };
    } else {
        editingCategory.value = null;
        form.value = {
            name: '',
            description: '',
            sort_order: 0,
        };
    }
    dialogOpen.value = true;
};

const submit = () => {
    const url = editingCategory.value
        ? `/admin/categories/${editingCategory.value.id}`
        : '/admin/categories';

    const method = editingCategory.value ? 'patch' : 'post';

    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
        },
    });
};

const deleteCategory = (id: number) => {
    if (confirm('Weet je zeker dat je deze categorie wilt verwijderen?')) {
        router.delete(`/admin/categories/${id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Categorieën" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Categorieën</h1>
                    <p class="text-sm text-muted-foreground">
                        Beheer productcategorieën
                    </p>
                </div>

                <Dialog v-model:open="dialogOpen">
                    <DialogTrigger as-child>
                        <Button @click="openDialog()">
                            Nieuwe categorie toevoegen
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>
                                {{ editingCategory ? 'Categorie bewerken' : 'Nieuwe categorie toevoegen' }}
                            </DialogTitle>
                            <DialogDescription>
                                Voeg een categorie toe of bewerk een bestaande categorie
                            </DialogDescription>
                        </DialogHeader>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="name">Naam</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Bijv. Vleeswaren, Worst, etc."
                                />
                                <InputError :message="errors?.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="description">Beschrijving (optioneel)</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Korte beschrijving van de categorie"
                                />
                                <InputError :message="errors?.description" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="sort_order">Sorteervolgorde</Label>
                                <Input
                                    id="sort_order"
                                    v-model.number="form.sort_order"
                                    type="number"
                                    min="0"
                                />
                                <InputError :message="errors?.sort_order" />
                            </div>

                            <div class="flex gap-2 justify-end">
                                <Button type="button" variant="outline" @click="dialogOpen = false">
                                    Annuleren
                                </Button>
                                <Button type="submit">
                                    {{ editingCategory ? 'Bijwerken' : 'Toevoegen' }}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div v-if="categories.length === 0" class="rounded-lg border border-dashed p-12 text-center">
                <p class="text-muted-foreground">Geen categorieën gevonden</p>
                <p class="text-sm text-muted-foreground mt-2">
                    Klik op "Nieuwe categorie toevoegen" om te beginnen
                </p>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="category in categories" :key="category.id">
                    <CardHeader>
                        <CardTitle>{{ category.name }}</CardTitle>
                        <CardDescription v-if="category.description">
                            {{ category.description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="text-sm">
                            <p class="text-muted-foreground">
                                {{ category.products_count }} {{ category.products_count === 1 ? 'product' : 'producten' }}
                            </p>
                            <p class="text-muted-foreground">
                                Sorteervolgorde: {{ category.sort_order }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Button size="sm" variant="outline" @click="openDialog(category)">
                                Bewerken
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="deleteCategory(category.id)"
                                :disabled="category.products_count > 0"
                            >
                                Verwijderen
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
