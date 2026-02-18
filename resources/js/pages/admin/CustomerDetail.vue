<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
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
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Building2, MapPin, Phone, Mail, CreditCard, FileText, Package, Edit } from 'lucide-vue-next';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

interface Customer {
    id: number;
    company_name: string;
    contact_person: string;
    email: string;
    phone_number: string;
    kvk_number: string;
    vat_number: string;
    bank_account: string;
    street_name: string;
    house_number: string;
    postal_code: string;
    city: string;
    customer_category: string | null;
    customer_category_label: string | null;
    discount_percentage: string | null;
    approved_at: string;
    created_at: string;
}

interface DeliveryAddress {
    id: number;
    name: string;
    street: string;
    house_number: string;
    postal_code: string;
    city: string;
    is_default: boolean;
}

interface Order {
    id: number;
    created_at: string;
    total: string;
    status: string;
    items_count: number;
}

const props = defineProps<{
    customer: Customer;
    deliveryAddresses: DeliveryAddress[];
    orders: Order[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Klanten',
        href: '/admin/customers',
    },
    {
        title: props.customer.company_name,
    },
];

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending: 'bg-yellow-500',
        confirmed: 'bg-blue-500',
        completed: 'bg-green-500',
        cancelled: 'bg-red-500',
    };
    return colors[status] || 'bg-gray-500';
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        pending: 'In afwachting',
        confirmed: 'Bevestigd',
        completed: 'Voltooid',
        cancelled: 'Geannuleerd',
    };
    return labels[status] || status;
};

const editDialogOpen = ref(false);
const editCategory = ref<string>('');
const editDiscount = ref<string>('');
const processing = ref(false);

const openEditDialog = () => {
    editCategory.value = props.customer.customer_category || '';
    editDiscount.value = props.customer.discount_percentage || '';
    editDialogOpen.value = true;
};

const updateCategoryAndDiscount = () => {
    if (!editCategory.value || !editDiscount.value) {
        return;
    }

    processing.value = true;
    router.patch(
        `/admin/customers/${props.customer.id}/category-discount`,
        {
            customer_category: editCategory.value,
            discount_percentage: editDiscount.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
                editDialogOpen.value = false;
            },
        }
    );
};

// General customer edit
const editCustomerDialogOpen = ref(false);
const form = useForm({
    company_name: props.customer.company_name,
    contact_person: props.customer.contact_person,
    phone_number: props.customer.phone_number,
    kvk_number: props.customer.kvk_number,
    vat_number: props.customer.vat_number,
    bank_account: props.customer.bank_account,
    street_name: props.customer.street_name,
    house_number: props.customer.house_number,
    postal_code: props.customer.postal_code,
    city: props.customer.city,
});

const openEditCustomerDialog = () => {
    form.company_name = props.customer.company_name;
    form.contact_person = props.customer.contact_person;
    form.phone_number = props.customer.phone_number;
    form.kvk_number = props.customer.kvk_number;
    form.vat_number = props.customer.vat_number;
    form.bank_account = props.customer.bank_account;
    form.street_name = props.customer.street_name;
    form.house_number = props.customer.house_number;
    form.postal_code = props.customer.postal_code;
    form.city = props.customer.city;
    form.clearErrors();
    editCustomerDialogOpen.value = true;
};

const updateCustomer = () => {
    form.patch(`/admin/customers/${props.customer.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editCustomerDialogOpen.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Klant: ${customer.company_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Customer Info Header -->
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <Building2 class="h-6 w-6" />
                    {{ customer.company_name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    Klant sinds {{ customer.created_at }}
                </p>
            </div>

            <!-- Customer Details Cards -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Contact Information -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Contactgegevens</CardTitle>
                                <CardDescription>Primaire contact informatie</CardDescription>
                            </div>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openEditCustomerDialog"
                            >
                                <Edit class="h-4 w-4 mr-2" />
                                Bewerken
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-start gap-3">
                            <Building2 class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Contactpersoon</p>
                                <p class="text-sm text-muted-foreground">{{ customer.contact_person }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Mail class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Email</p>
                                <p class="text-sm text-muted-foreground">{{ customer.email }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Phone class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Telefoon</p>
                                <p class="text-sm text-muted-foreground">{{ customer.phone_number }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Business Information -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Bedrijfsgegevens</CardTitle>
                                <CardDescription>KVK en BTW informatie</CardDescription>
                            </div>
                            <Button
                                v-if="customer.approved_at"
                                size="sm"
                                variant="outline"
                                @click="openEditDialog"
                            >
                                <Edit class="h-4 w-4 mr-2" />
                                {{ customer.customer_category ? 'Aanpassen' : 'Instellen' }}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-start gap-3">
                            <FileText class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">KVK Nummer</p>
                                <p class="text-sm text-muted-foreground">{{ customer.kvk_number }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <FileText class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">BTW Nummer</p>
                                <p class="text-sm text-muted-foreground">{{ customer.vat_number }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <CreditCard class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">IBAN</p>
                                <p class="text-sm text-muted-foreground">{{ customer.bank_account }}</p>
                            </div>
                        </div>
                        <div v-if="customer.customer_category_label" class="flex items-start gap-3">
                            <Building2 class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Klantcategorie</p>
                                <p class="text-sm text-muted-foreground">{{ customer.customer_category_label }}</p>
                            </div>
                        </div>
                        <div v-if="customer.discount_percentage" class="flex items-start gap-3">
                            <FileText class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Kortingspercentage</p>
                                <p class="text-sm text-muted-foreground">{{ customer.discount_percentage }}%</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Address Information -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Adresgegevens</CardTitle>
                                <CardDescription>Primair bedrijfsadres</CardDescription>
                            </div>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openEditCustomerDialog"
                            >
                                <Edit class="h-4 w-4 mr-2" />
                                Bewerken
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-start gap-3">
                            <MapPin class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Adres</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ customer.street_name }} {{ customer.house_number }}<br>
                                    {{ customer.postal_code }} {{ customer.city }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Delivery Addresses -->
                <Card>
                    <CardHeader>
                        <CardTitle>Afleveradressen</CardTitle>
                        <CardDescription>{{ deliveryAddresses.length }} geregistreerd(e) adres(sen)</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="deliveryAddresses.length === 0" class="text-sm text-muted-foreground">
                            Geen afleveradressen
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="address in deliveryAddresses"
                                :key="address.id"
                                class="flex items-start gap-3 pb-3 border-b last:border-b-0 last:pb-0"
                            >
                                <MapPin class="h-4 w-4 text-muted-foreground mt-0.5" />
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium">{{ address.name }}</p>
                                        <Badge v-if="address.is_default" variant="secondary" class="text-xs">
                                            Standaard
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground">
                                        {{ address.street }} {{ address.house_number }}<br>
                                        {{ address.postal_code }} {{ address.city }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Order History -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Package class="h-5 w-5" />
                        Bestelgeschiedenis
                    </CardTitle>
                    <CardDescription>Alle bestellingen van deze klant</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="orders.length === 0" class="text-center py-8 text-muted-foreground">
                        Nog geen bestellingen geplaatst
                    </div>
                    <div v-else class="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Order #</TableHead>
                                    <TableHead>Datum</TableHead>
                                    <TableHead>Aantal Producten</TableHead>
                                    <TableHead>Totaal</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="order in orders" :key="order.id">
                                    <TableCell class="font-medium">#{{ order.id }}</TableCell>
                                    <TableCell>{{ order.created_at }}</TableCell>
                                    <TableCell>{{ order.items_count }}</TableCell>
                                    <TableCell>€{{ order.total }}</TableCell>
                                    <TableCell>
                                        <Badge :class="getStatusColor(order.status)">
                                            {{ getStatusLabel(order.status) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Link
                                            :href="admin.orders.show(order.id).url"
                                            class="text-sm text-primary hover:underline"
                                        >
                                            Bekijk details
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Edit Category and Discount Dialog -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Klantcategorie & Korting Aanpassen</DialogTitle>
                    <DialogDescription>
                        Wijzig de klantcategorie en kortingspercentage.
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4 space-y-6">
                    <div>
                        <Label class="mb-3 block">Klantcategorie *</Label>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    id="edit_groothandel"
                                    value="groothandel"
                                    v-model="editCategory"
                                    class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label for="edit_groothandel" class="cursor-pointer font-normal">
                                    Groothandel
                                </Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    id="edit_broodjeszaak"
                                    value="broodjeszaak"
                                    v-model="editCategory"
                                    class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label for="edit_broodjeszaak" class="cursor-pointer font-normal">
                                    Broodjeszaak
                                </Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    id="edit_horeca"
                                    value="horeca"
                                    v-model="editCategory"
                                    class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label for="edit_horeca" class="cursor-pointer font-normal">
                                    Horeca
                                </Label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <Label class="mb-3 block">Kortingspercentage *</Label>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    id="edit_discount_0"
                                    value="0"
                                    v-model="editDiscount"
                                    class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label for="edit_discount_0" class="cursor-pointer font-normal">
                                    0% (geen korting)
                                </Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    id="edit_discount_2_5"
                                    value="2.5"
                                    v-model="editDiscount"
                                    class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label for="edit_discount_2_5" class="cursor-pointer font-normal">
                                    2,5%
                                </Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    id="edit_discount_3"
                                    value="3"
                                    v-model="editDiscount"
                                    class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label for="edit_discount_3" class="cursor-pointer font-normal">
                                    3%
                                </Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    id="edit_discount_5"
                                    value="5"
                                    v-model="editDiscount"
                                    class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label for="edit_discount_5" class="cursor-pointer font-normal">
                                    5%
                                </Label>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="editDialogOpen = false"
                        :disabled="processing"
                    >
                        Annuleren
                    </Button>
                    <Button
                        @click="updateCategoryAndDiscount"
                        :disabled="!editCategory || !editDiscount || processing"
                    >
                        Opslaan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Customer Dialog -->
        <Dialog v-model:open="editCustomerDialogOpen">
            <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Klantgegevens Bewerken</DialogTitle>
                    <DialogDescription>
                        Wijzig de klantgegevens. Het email adres kan niet worden gewijzigd.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="updateCustomer" class="space-y-6">
                    <!-- Contact Information -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold">Contactgegevens</h3>

                        <div>
                            <Label for="company_name">Bedrijfsnaam *</Label>
                            <Input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.company_name" class="mt-2" />
                        </div>

                        <div>
                            <Label for="contact_person">Contactpersoon *</Label>
                            <Input
                                id="contact_person"
                                v-model="form.contact_person"
                                type="text"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.contact_person" class="mt-2" />
                        </div>

                        <div>
                            <Label for="email">Email adres (niet wijzigbaar)</Label>
                            <Input
                                id="email"
                                :value="customer.email"
                                type="email"
                                class="mt-1"
                                readonly
                                disabled
                            />
                        </div>

                        <div>
                            <Label for="phone_number">Telefoonnummer *</Label>
                            <Input
                                id="phone_number"
                                v-model="form.phone_number"
                                type="text"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.phone_number" class="mt-2" />
                        </div>
                    </div>

                    <!-- Business Information -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold">Bedrijfsgegevens</h3>

                        <div>
                            <Label for="kvk_number">KVK Nummer *</Label>
                            <Input
                                id="kvk_number"
                                v-model="form.kvk_number"
                                type="text"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.kvk_number" class="mt-2" />
                        </div>

                        <div>
                            <Label for="vat_number">BTW Nummer *</Label>
                            <Input
                                id="vat_number"
                                v-model="form.vat_number"
                                type="text"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.vat_number" class="mt-2" />
                        </div>

                        <div>
                            <Label for="bank_account">IBAN *</Label>
                            <Input
                                id="bank_account"
                                v-model="form.bank_account"
                                type="text"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.bank_account" class="mt-2" />
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold">Adresgegevens</h3>

                        <div>
                            <Label for="street_name">Straatnaam *</Label>
                            <Input
                                id="street_name"
                                v-model="form.street_name"
                                type="text"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.street_name" class="mt-2" />
                        </div>

                        <div>
                            <Label for="house_number">Huisnummer *</Label>
                            <Input
                                id="house_number"
                                v-model="form.house_number"
                                type="text"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.house_number" class="mt-2" />
                        </div>

                        <div>
                            <Label for="postal_code">Postcode *</Label>
                            <Input
                                id="postal_code"
                                v-model="form.postal_code"
                                type="text"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.postal_code" class="mt-2" />
                        </div>

                        <div>
                            <Label for="city">Plaats *</Label>
                            <Input
                                id="city"
                                v-model="form.city"
                                type="text"
                                class="mt-1"
                                required
                            />
                            <InputError :message="form.errors.city" class="mt-2" />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="editCustomerDialogOpen = false"
                            :disabled="form.processing"
                        >
                            Annuleren
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                        >
                            Opslaan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
