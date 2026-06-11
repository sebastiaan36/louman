<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { Building2, MapPin, Phone, Mail, CreditCard, FileText, Package, Edit, Plus, Trash2, Ban, CheckCircle2 } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { orderStatusClasses, orderStatusLabel } from '@/lib/orderStatus';
import { formatPrice } from '@/lib/price';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';

interface Customer {
    id: number;
    company_name: string;
    customer_number: string | null;
    contact_person: string;
    email: string;
    has_account: boolean;
    phone_number: string;
    packing_slip_email: string | null;
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
    delivery_day: string | null;
    show_on_map: boolean;
    approved_at: string;
    created_at: string;
    is_active: boolean;
    deactivated_at: string | null;
    can_delete: boolean;
}

interface DeliveryAddress {
    id: number;
    name: string;
    street_name: string;
    house_number: string;
    postal_code: string;
    city: string;
    notes: string | null;
    is_default: boolean;
}

interface Order {
    id: number;
    created_at: string;
    total: string;
    status: string;
    items_count: number;
}

interface FavoriteProduct {
    id: number;
    title: string;
    article_number: string | null;
    standard_price: string;
    custom_price: string | null;
}

const props = defineProps<{
    customer: Customer;
    deliveryAddresses: DeliveryAddress[];
    orders: Order[];
    favoriteProducts: FavoriteProduct[];
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

// Custom prices for favorite products (sparse: only deviating prices)
const customPriceInputs = ref<Record<number, string>>(
    Object.fromEntries(props.favoriteProducts.map((p) => [p.id, p.custom_price ?? ''])),
);
const savingPrices = ref(false);

const saveCustomPrices = () => {
    savingPrices.value = true;
    router.post(
        `/admin/customers/${props.customer.id}/product-prices`,
        {
            prices: props.favoriteProducts.map((p) => ({
                product_id: p.id,
                custom_price: customPriceInputs.value[p.id] === '' ? null : customPriceInputs.value[p.id],
            })),
        },
        {
            preserveScroll: true,
            onFinish: () => {
                savingPrices.value = false;
            },
        },
    );
};

// Activation / deactivation / deletion
const deactivateDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const statusProcessing = ref(false);

// Account invitation (for customers without an account)
const inviteDialogOpen = ref(false);
const inviteForm = useForm({ email: '' });

const openInviteDialog = () => {
    inviteForm.reset();
    inviteForm.clearErrors();
    inviteDialogOpen.value = true;
};

const sendInvitation = () => {
    inviteForm.post(`/admin/customers/${props.customer.id}/invite`, {
        preserveScroll: true,
        onSuccess: () => {
            inviteDialogOpen.value = false;
        },
    });
};

const deactivateCustomer = () => {
    statusProcessing.value = true;
    router.post(`/admin/customers/${props.customer.id}/deactivate`, {}, {
        preserveScroll: true,
        onFinish: () => {
            statusProcessing.value = false;
            deactivateDialogOpen.value = false;
        },
    });
};

const activateCustomer = () => {
    statusProcessing.value = true;
    router.post(`/admin/customers/${props.customer.id}/activate`, {}, {
        preserveScroll: true,
        onFinish: () => {
            statusProcessing.value = false;
        },
    });
};

const deleteCustomer = () => {
    statusProcessing.value = true;
    router.delete(`/admin/customers/${props.customer.id}`, {
        onFinish: () => {
            statusProcessing.value = false;
            deleteDialogOpen.value = false;
        },
    });
};

const editDialogOpen = ref(false);
const editCategory = ref<string>('');
const editDiscount = ref<string>('');
const editDeliveryDay = ref<string>('');
const editShowOnMap = ref<boolean>(true);
const processing = ref(false);

const deliveryDays = [
    { value: 'maandag', label: 'Maandag' },
    { value: 'dinsdag', label: 'Dinsdag' },
    { value: 'woensdag', label: 'Woensdag' },
    { value: 'donderdag', label: 'Donderdag' },
    { value: 'vrijdag', label: 'Vrijdag' },
    { value: 'zaterdag', label: 'Zaterdag' },
    { value: 'zondag', label: 'Zondag' },
    { value: 'ophalen', label: 'Ophalen' },
];

const openEditDialog = () => {
    editCategory.value = props.customer.customer_category || '';
    editDiscount.value = props.customer.discount_percentage || '';
    editDeliveryDay.value = props.customer.delivery_day || '';
    editShowOnMap.value = props.customer.show_on_map ?? true;
    editDialogOpen.value = true;
};

const updateCategoryAndDiscount = () => {
    if (!editCategory.value || !editDeliveryDay.value) {
        return;
    }

    processing.value = true;
    router.patch(
        `/admin/customers/${props.customer.id}/category-discount`,
        {
            customer_category: editCategory.value,
            discount_percentage: editDiscount.value,
            delivery_day: editDeliveryDay.value,
            show_on_map: editShowOnMap.value,
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
    customer_number: props.customer.customer_number || '',
    contact_person: props.customer.contact_person,
    phone_number: props.customer.phone_number,
    kvk_number: props.customer.kvk_number,
    vat_number: props.customer.vat_number,
    bank_account: props.customer.bank_account,
    street_name: props.customer.street_name,
    house_number: props.customer.house_number,
    postal_code: props.customer.postal_code,
    city: props.customer.city,
    packing_slip_email: props.customer.packing_slip_email || '',
});

const openEditCustomerDialog = () => {
    form.company_name = props.customer.company_name;
    form.customer_number = props.customer.customer_number || '';
    form.contact_person = props.customer.contact_person;
    form.phone_number = props.customer.phone_number;
    form.kvk_number = props.customer.kvk_number;
    form.vat_number = props.customer.vat_number;
    form.bank_account = props.customer.bank_account;
    form.street_name = props.customer.street_name;
    form.house_number = props.customer.house_number;
    form.postal_code = props.customer.postal_code;
    form.city = props.customer.city;
    form.packing_slip_email = props.customer.packing_slip_email || '';
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

// Delivery address management
const addressDialogOpen = ref(false);
const editingAddress = ref<DeliveryAddress | null>(null);
const addressForm = useForm({
    name: '',
    street_name: '',
    house_number: '',
    postal_code: '',
    city: '',
    notes: '',
    is_default: false,
});

const openAddressDialog = (address?: DeliveryAddress) => {
    if (address) {
        editingAddress.value = address;
        addressForm.name = address.name;
        addressForm.street_name = address.street_name;
        addressForm.house_number = address.house_number;
        addressForm.postal_code = address.postal_code;
        addressForm.city = address.city;
        addressForm.notes = address.notes || '';
        addressForm.is_default = address.is_default;
    } else {
        editingAddress.value = null;
        addressForm.reset();
    }
    addressForm.clearErrors();
    addressDialogOpen.value = true;
};

const submitAddress = () => {
    if (editingAddress.value) {
        addressForm.patch(
            `/admin/customers/${props.customer.id}/delivery-addresses/${editingAddress.value.id}`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    addressDialogOpen.value = false;
                },
            }
        );
    } else {
        addressForm.post(
            `/admin/customers/${props.customer.id}/delivery-addresses`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    addressDialogOpen.value = false;
                },
            }
        );
    }
};

const deleteAddress = (addressId: number) => {
    if (confirm('Weet je zeker dat je dit afleveradres wilt verwijderen?')) {
        router.delete(
            `/admin/customers/${props.customer.id}/delivery-addresses/${addressId}`,
            { preserveScroll: true }
        );
    }
};
</script>

<template>
    <Head :title="`Klant: ${customer.company_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Customer Info Header -->
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <Building2 class="h-6 w-6" />
                        {{ customer.company_name }}
                        <Badge :variant="customer.is_active ? 'default' : 'destructive'">
                            {{ customer.is_active ? 'Actief' : 'Inactief' }}
                        </Badge>
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        <span v-if="customer.customer_number">Klantnummer {{ customer.customer_number }} · </span>
                        Klant sinds {{ customer.created_at }}
                        <span v-if="!customer.is_active && customer.deactivated_at">
                            · gedeactiveerd op {{ customer.deactivated_at }}
                        </span>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        v-if="customer.is_active"
                        variant="outline"
                        @click="deactivateDialogOpen = true"
                    >
                        <Ban class="h-4 w-4 mr-2" />
                        Deactiveren
                    </Button>
                    <Button
                        v-else
                        variant="outline"
                        :disabled="statusProcessing"
                        @click="activateCustomer"
                    >
                        <CheckCircle2 class="h-4 w-4 mr-2" />
                        Activeren
                    </Button>
                    <Button
                        v-if="customer.can_delete"
                        variant="destructive"
                        @click="deleteDialogOpen = true"
                    >
                        <Trash2 class="h-4 w-4 mr-2" />
                        Verwijderen
                    </Button>
                </div>
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
                            <div class="flex-1">
                                <p class="text-sm font-medium">Email</p>
                                <p v-if="customer.email" class="text-sm text-muted-foreground">{{ customer.email }}</p>
                                <template v-else>
                                    <p class="text-sm text-muted-foreground">Nog geen account</p>
                                    <Button size="sm" variant="outline" class="mt-2" @click="openInviteDialog">
                                        <Mail class="h-4 w-4 mr-2" />
                                        Uitnodiging versturen
                                    </Button>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Phone class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Telefoon</p>
                                <p class="text-sm text-muted-foreground">{{ customer.phone_number }}</p>
                            </div>
                        </div>
                        <div v-if="customer.packing_slip_email" class="flex items-start gap-3">
                            <Mail class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Pakbon Email</p>
                                <p class="text-sm text-muted-foreground">{{ customer.packing_slip_email }}</p>
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
                        <div v-if="customer.delivery_day" class="flex items-start gap-3">
                            <MapPin class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Leverdag</p>
                                <p class="text-sm text-muted-foreground capitalize">{{ customer.delivery_day }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <MapPin class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Toon op kaart (louman-jordaan.nl)</p>
                                <p class="text-sm text-muted-foreground">{{ customer.show_on_map ? 'Ja' : 'Nee' }}</p>
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
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Afleveradressen</CardTitle>
                                <CardDescription>{{ deliveryAddresses.length }} geregistreerd(e) adres(sen)</CardDescription>
                            </div>
                            <Button size="sm" variant="outline" @click="openAddressDialog()">
                                <Plus class="h-4 w-4 mr-2" />
                                Toevoegen
                            </Button>
                        </div>
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
                                <MapPin class="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium">{{ address.name }}</p>
                                        <Badge v-if="address.is_default" variant="secondary" class="text-xs">
                                            Standaard
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground">
                                        {{ address.street_name }} {{ address.house_number }}<br>
                                        {{ address.postal_code }} {{ address.city }}
                                    </p>
                                    <p v-if="address.notes" class="text-xs text-muted-foreground mt-1 italic">
                                        {{ address.notes }}
                                    </p>
                                </div>
                                <div class="flex gap-1 shrink-0">
                                    <Button size="sm" variant="ghost" @click="openAddressDialog(address)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button size="sm" variant="ghost" class="text-destructive hover:text-destructive" @click="deleteAddress(address.id)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
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
                                    <TableCell>€{{ formatPrice(order.total) }}</TableCell>
                                    <TableCell>
                                        <Badge :class="orderStatusClasses(order.status)">
                                            {{ orderStatusLabel(order.status) }}
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

            <!-- Custom Prices (favorites) -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Package class="h-5 w-5" />
                        Aangepaste prijzen
                    </CardTitle>
                    <CardDescription>
                        Stel afwijkende prijzen in voor producten in de Quick Order-lijst van deze klant.
                        Een aangepaste prijs geldt zonder kortingspercentage. Laat leeg (of gelijk aan de
                        standaardprijs) om de standaardprijs te gebruiken.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="favoriteProducts.length === 0" class="text-center py-8 text-muted-foreground">
                        Deze klant heeft nog geen producten in de Quick Order-lijst.
                    </div>
                    <div v-else class="space-y-4">
                        <div class="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Artikelnr</TableHead>
                                        <TableHead>Product</TableHead>
                                        <TableHead class="text-right">Standaardprijs</TableHead>
                                        <TableHead class="w-48">Aangepaste prijs</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="product in favoriteProducts" :key="product.id">
                                        <TableCell class="font-mono text-sm">{{ product.article_number || '-' }}</TableCell>
                                        <TableCell class="font-medium">{{ product.title }}</TableCell>
                                        <TableCell class="text-right text-muted-foreground">€ {{ formatPrice(product.standard_price) }}</TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <span class="text-muted-foreground">€</span>
                                                <Input
                                                    v-model="customPriceInputs[product.id]"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    :placeholder="product.standard_price"
                                                    class="w-32"
                                                />
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                        <div class="flex justify-end">
                            <Button :disabled="savingPrices" @click="saveCustomPrices">
                                {{ savingPrices ? 'Opslaan...' : 'Prijzen opslaan' }}
                            </Button>
                        </div>
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
                        <Label class="mb-3 block">Kortingspercentage</Label>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    id="edit_discount_none"
                                    value=""
                                    v-model="editDiscount"
                                    class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label for="edit_discount_none" class="cursor-pointer font-normal">
                                    Geen korting
                                </Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    id="edit_discount_1"
                                    value="1"
                                    v-model="editDiscount"
                                    class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label for="edit_discount_1" class="cursor-pointer font-normal">
                                    1%
                                </Label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input
                                    type="radio"
                                    id="edit_discount_2"
                                    value="2"
                                    v-model="editDiscount"
                                    class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label for="edit_discount_2" class="cursor-pointer font-normal">
                                    2%
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
                                    id="edit_discount_4"
                                    value="4"
                                    v-model="editDiscount"
                                    class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                                />
                                <Label for="edit_discount_4" class="cursor-pointer font-normal">
                                    4%
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

                    <div class="mt-6">
                        <Label for="edit_delivery_day" class="mb-2 block">Leverdag *</Label>
                        <select
                            id="edit_delivery_day"
                            v-model="editDeliveryDay"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        >
                            <option value="" disabled>Selecteer een dag</option>
                            <option v-for="day in deliveryDays" :key="day.value" :value="day.value">
                                {{ day.label }}
                            </option>
                        </select>
                    </div>

                    <div class="mt-6 flex items-center space-x-2">
                        <Checkbox
                            id="edit_show_on_map"
                            v-model="editShowOnMap"
                        />
                        <Label for="edit_show_on_map" class="cursor-pointer font-normal leading-snug">
                            Toon bedrijf op kaart op louman-jordaan.nl
                        </Label>
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
                        :disabled="!editCategory || !editDeliveryDay || processing"
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
                        Wijzig de klantgegevens van deze klant.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="updateCustomer" class="space-y-6">
                    <!-- Contact Information -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold">Contactgegevens</h3>

                        <div>
                            <Label for="company_name">Bedrijfsnaam</Label>
                            <Input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.company_name" class="mt-2" />
                        </div>

                        <div>
                            <Label for="customer_number">Klantnummer</Label>
                            <Input
                                id="customer_number"
                                v-model="form.customer_number"
                                type="text"
                                inputmode="numeric"
                                maxlength="4"
                                class="mt-1"
                                placeholder="1 t/m 4 cijfers, optioneel"
                            />
                            <InputError :message="form.errors.customer_number" class="mt-2" />
                        </div>

                        <div>
                            <Label for="contact_person">Contactpersoon</Label>
                            <Input
                                id="contact_person"
                                v-model="form.contact_person"
                                type="text"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.contact_person" class="mt-2" />
                        </div>

                        <div>
                            <Label for="phone_number">Telefoonnummer</Label>
                            <Input
                                id="phone_number"
                                v-model="form.phone_number"
                                type="text"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.phone_number" class="mt-2" />
                        </div>

                        <div>
                            <Label for="packing_slip_email">Pakbon Email</Label>
                            <Input
                                id="packing_slip_email"
                                v-model="form.packing_slip_email"
                                type="email"
                                class="mt-1"
                                placeholder="optioneel"
                            />
                            <InputError :message="form.errors.packing_slip_email" class="mt-2" />
                        </div>

                    </div>

                    <!-- Business Information -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold">Bedrijfsgegevens</h3>

                        <div>
                            <Label for="kvk_number">KVK Nummer</Label>
                            <Input
                                id="kvk_number"
                                v-model="form.kvk_number"
                                type="text"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.kvk_number" class="mt-2" />
                        </div>

                        <div>
                            <Label for="vat_number">BTW Nummer</Label>
                            <Input
                                id="vat_number"
                                v-model="form.vat_number"
                                type="text"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.vat_number" class="mt-2" />
                        </div>

                        <div>
                            <Label for="bank_account">IBAN</Label>
                            <Input
                                id="bank_account"
                                v-model="form.bank_account"
                                type="text"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.bank_account" class="mt-2" />
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold">Adresgegevens</h3>

                        <div>
                            <Label for="street_name">Straatnaam</Label>
                            <Input
                                id="street_name"
                                v-model="form.street_name"
                                type="text"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.street_name" class="mt-2" />
                        </div>

                        <div>
                            <Label for="house_number">Huisnummer</Label>
                            <Input
                                id="house_number"
                                v-model="form.house_number"
                                type="text"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.house_number" class="mt-2" />
                        </div>

                        <div>
                            <Label for="postal_code">Postcode</Label>
                            <Input
                                id="postal_code"
                                v-model="form.postal_code"
                                type="text"
                                class="mt-1"
                            />
                            <InputError :message="form.errors.postal_code" class="mt-2" />
                        </div>

                        <div>
                            <Label for="city">Plaats</Label>
                            <Input
                                id="city"
                                v-model="form.city"
                                type="text"
                                class="mt-1"
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

        <!-- Delivery Address Dialog -->
        <Dialog v-model:open="addressDialogOpen">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingAddress ? 'Afleveradres bewerken' : 'Afleveradres toevoegen' }}</DialogTitle>
                    <DialogDescription>
                        {{ editingAddress ? 'Wijzig de gegevens van dit afleveradres.' : 'Voeg een nieuw afleveradres toe voor deze klant.' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitAddress" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="addr_name">Naam adres *</Label>
                        <Input
                            id="addr_name"
                            v-model="addressForm.name"
                            type="text"
                            required
                            placeholder="Bijv. Hoofdkantoor, Magazijn, etc."
                        />
                        <InputError :message="addressForm.errors.name" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="addr_street_name">Straatnaam *</Label>
                            <Input
                                id="addr_street_name"
                                v-model="addressForm.street_name"
                                type="text"
                                required
                            />
                            <InputError :message="addressForm.errors.street_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="addr_house_number">Huisnummer *</Label>
                            <Input
                                id="addr_house_number"
                                v-model="addressForm.house_number"
                                type="text"
                                required
                            />
                            <InputError :message="addressForm.errors.house_number" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="addr_postal_code">Postcode *</Label>
                            <Input
                                id="addr_postal_code"
                                v-model="addressForm.postal_code"
                                type="text"
                                required
                            />
                            <InputError :message="addressForm.errors.postal_code" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="addr_city">Plaats *</Label>
                            <Input
                                id="addr_city"
                                v-model="addressForm.city"
                                type="text"
                                required
                            />
                            <InputError :message="addressForm.errors.city" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="addr_notes">Notities (optioneel)</Label>
                        <Textarea
                            id="addr_notes"
                            v-model="addressForm.notes"
                            placeholder="Extra informatie over dit adres"
                        />
                        <InputError :message="addressForm.errors.notes" />
                    </div>

                    <div class="flex items-center space-x-2">
                        <Checkbox
                            id="addr_is_default"
                            v-model="addressForm.is_default"
                        />
                        <Label for="addr_is_default" class="text-sm font-normal cursor-pointer">
                            Stel in als standaard afleveradres
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="addressDialogOpen = false"
                            :disabled="addressForm.processing"
                        >
                            Annuleren
                        </Button>
                        <Button type="submit" :disabled="addressForm.processing">
                            {{ editingAddress ? 'Bijwerken' : 'Toevoegen' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Deactivate Confirmation Dialog -->
        <Dialog :open="deactivateDialogOpen" @update:open="deactivateDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Klant deactiveren</DialogTitle>
                    <DialogDescription>
                        Weet je zeker dat je <strong>{{ customer.company_name }}</strong> wilt deactiveren?
                        De klant kan niet meer inloggen of bestellen. Alle gegevens en bestelhistorie
                        blijven bewaard en je kunt de klant later weer activeren.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deactivateDialogOpen = false">
                        Annuleren
                    </Button>
                    <Button variant="destructive" :disabled="statusProcessing" @click="deactivateCustomer">
                        Deactiveren
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Send Invitation Dialog -->
        <Dialog :open="inviteDialogOpen" @update:open="inviteDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Account-uitnodiging versturen</DialogTitle>
                    <DialogDescription>
                        Vul het e-mailadres van <strong>{{ customer.company_name }}</strong> in.
                        De klant ontvangt een uitnodiging om een wachtwoord in te stellen en de
                        registratie af te ronden.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="invite_email">E-mailadres</Label>
                    <Input
                        id="invite_email"
                        v-model="inviteForm.email"
                        type="email"
                        placeholder="email@bedrijf.nl"
                        autofocus
                        @keyup.enter="sendInvitation"
                    />
                    <InputError :message="inviteForm.errors.email" />
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="inviteDialogOpen = false">Annuleren</Button>
                    <Button :disabled="inviteForm.processing" @click="sendInvitation">
                        {{ inviteForm.processing ? 'Versturen...' : 'Uitnodiging versturen' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="deleteDialogOpen" @update:open="deleteDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Klant verwijderen</DialogTitle>
                    <DialogDescription>
                        Weet je zeker dat je <strong>{{ customer.company_name }}</strong> definitief wilt
                        verwijderen? Dit verwijdert ook het gekoppelde account, afleveradressen, favorieten
                        en winkelwagen. Deze actie kan niet ongedaan worden gemaakt.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">
                        Annuleren
                    </Button>
                    <Button variant="destructive" :disabled="statusProcessing" @click="deleteCustomer">
                        Definitief verwijderen
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
