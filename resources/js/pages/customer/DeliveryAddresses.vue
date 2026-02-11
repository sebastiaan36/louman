<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface DeliveryAddress {
    id: number;
    name: string;
    street_name: string;
    house_number: string;
    postal_code: string;
    city: string;
    notes?: string;
    is_default: boolean;
}

const props = defineProps<{
    addresses: DeliveryAddress[];
    errors?: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Afleveradressen',
    },
];

const dialogOpen = ref(false);
const editingAddress = ref<DeliveryAddress | null>(null);

const form = ref({
    name: '',
    street_name: '',
    house_number: '',
    postal_code: '',
    city: '',
    notes: '',
    is_default: false,
});

const openDialog = (address?: DeliveryAddress) => {
    if (address) {
        editingAddress.value = address;
        form.value = {
            name: address.name,
            street_name: address.street_name,
            house_number: address.house_number,
            postal_code: address.postal_code,
            city: address.city,
            notes: address.notes || '',
            is_default: address.is_default,
        };
    } else {
        editingAddress.value = null;
        form.value = {
            name: '',
            street_name: '',
            house_number: '',
            postal_code: '',
            city: '',
            notes: '',
            is_default: false,
        };
    }
    dialogOpen.value = true;
};

const submit = () => {
    const url = editingAddress.value
        ? `/customer/delivery-addresses/${editingAddress.value.id}`
        : '/customer/delivery-addresses';

    const method = editingAddress.value ? 'patch' : 'post';

    router[method](url, form.value, {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
        },
    });
};

const deleteAddress = (id: number) => {
    if (confirm('Weet je zeker dat je dit afleveradres wilt verwijderen?')) {
        router.delete(`/customer/delivery-addresses/${id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Afleveradressen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Afleveradressen</h1>
                    <p class="text-sm text-muted-foreground">
                        Beheer je afleveradressen
                    </p>
                </div>

                <Dialog v-model:open="dialogOpen">
                    <DialogTrigger as-child>
                        <Button @click="openDialog()">
                            Nieuw adres toevoegen
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>
                                {{ editingAddress ? 'Adres bewerken' : 'Nieuw adres toevoegen' }}
                            </DialogTitle>
                            <DialogDescription>
                                Voeg een afleveradres toe of bewerk een bestaand adres
                            </DialogDescription>
                        </DialogHeader>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="name">Naam adres</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Bijv. Hoofdkantoor, Magazijn, etc."
                                />
                                <InputError :message="errors?.name" />
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="street_name">Straatnaam</Label>
                                    <Input
                                        id="street_name"
                                        v-model="form.street_name"
                                        type="text"
                                        required
                                    />
                                    <InputError :message="errors?.street_name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="house_number">Huisnummer</Label>
                                    <Input
                                        id="house_number"
                                        v-model="form.house_number"
                                        type="text"
                                        required
                                    />
                                    <InputError :message="errors?.house_number" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="postal_code">Postcode</Label>
                                    <Input
                                        id="postal_code"
                                        v-model="form.postal_code"
                                        type="text"
                                        required
                                    />
                                    <InputError :message="errors?.postal_code" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="city">Plaats</Label>
                                    <Input
                                        id="city"
                                        v-model="form.city"
                                        type="text"
                                        required
                                    />
                                    <InputError :message="errors?.city" />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="notes">Notities (optioneel)</Label>
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    placeholder="Extra informatie over dit adres"
                                />
                                <InputError :message="errors?.notes" />
                            </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="is_default"
                                    v-model:checked="form.is_default"
                                />
                                <Label for="is_default" class="text-sm font-normal cursor-pointer">
                                    Stel in als standaard afleveradres
                                </Label>
                            </div>

                            <div class="flex gap-2 justify-end">
                                <Button type="button" variant="outline" @click="dialogOpen = false">
                                    Annuleren
                                </Button>
                                <Button type="submit">
                                    {{ editingAddress ? 'Bijwerken' : 'Toevoegen' }}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div v-if="addresses.length === 0" class="rounded-lg border border-dashed p-12 text-center">
                <p class="text-muted-foreground">Geen afleveradressen gevonden</p>
                <p class="text-sm text-muted-foreground mt-2">
                    Klik op "Nieuw adres toevoegen" om te beginnen
                </p>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="address in addresses" :key="address.id" :class="{ 'ring-2 ring-primary': address.is_default }">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            {{ address.name }}
                            <span v-if="address.is_default" class="text-xs font-normal px-2 py-1 bg-primary/10 text-primary rounded">
                                Standaard
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="text-sm">
                            <p>{{ address.street_name }} {{ address.house_number }}</p>
                            <p>{{ address.postal_code }} {{ address.city }}</p>
                            <p v-if="address.notes" class="mt-2 text-muted-foreground">
                                {{ address.notes }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Button size="sm" variant="outline" @click="openDialog(address)">
                                Bewerken
                            </Button>
                            <Button size="sm" variant="destructive" @click="deleteAddress(address.id)">
                                Verwijderen
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
