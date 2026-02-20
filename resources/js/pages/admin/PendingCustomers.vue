<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
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

interface Customer {
    id: number;
    company_name: string;
    contact_person: string;
    email: string;
    phone_number: string;
    kvk_number: string;
    vat_number: string;
    bank_account: string;
    city: string;
    registered_at: string;
}

defineProps<{
    customers: Customer[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Klanten Goedkeuring',
    },
];

const approvalDialogOpen = ref(false);
const selectedCustomerId = ref<number | null>(null);
const selectedCategory = ref<string>('');
const selectedDiscount = ref<string>('');
const selectedDeliveryDay = ref<string>('');
const processing = ref(false);

const deliveryDays = [
    { value: 'maandag', label: 'Maandag' },
    { value: 'dinsdag', label: 'Dinsdag' },
    { value: 'woensdag', label: 'Woensdag' },
    { value: 'donderdag', label: 'Donderdag' },
    { value: 'vrijdag', label: 'Vrijdag' },
    { value: 'zaterdag', label: 'Zaterdag' },
    { value: 'zondag', label: 'Zondag' },
];

const openApprovalDialog = (customerId: number) => {
    selectedCustomerId.value = customerId;
    selectedCategory.value = '';
    selectedDiscount.value = '';
    selectedDeliveryDay.value = '';
    approvalDialogOpen.value = true;
};

const approveCustomer = () => {
    if (!selectedCategory.value || !selectedDiscount.value || !selectedDeliveryDay.value) {
        return;
    }

    processing.value = true;
    router.post(
        `/admin/customers/${selectedCustomerId.value}/approve`,
        {
            customer_category: selectedCategory.value,
            discount_percentage: selectedDiscount.value,
            delivery_day: selectedDeliveryDay.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
                approvalDialogOpen.value = false;
                selectedCustomerId.value = null;
                selectedCategory.value = '';
                selectedDiscount.value = '';
                selectedDeliveryDay.value = '';
            },
        }
    );
};
</script>

<template>
    <Head title="Klanten Goedkeuring" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-bold">Wachtende Klanten</h1>
                <p class="text-sm text-muted-foreground">
                    Bekijk en keur nieuwe klantregistraties goed
                </p>
            </div>

            <div v-if="customers.length === 0" class="rounded-lg border border-dashed p-12 text-center">
                <p class="text-muted-foreground">Geen klanten wachten op goedkeuring</p>
            </div>

            <div v-else class="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Bedrijf</TableHead>
                            <TableHead>Contactpersoon</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Telefoon</TableHead>
                            <TableHead>KvK</TableHead>
                            <TableHead>Stad</TableHead>
                            <TableHead>Registratie</TableHead>
                            <TableHead>Acties</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="customer in customers" :key="customer.id">
                            <TableCell class="font-medium">
                                {{ customer.company_name }}
                            </TableCell>
                            <TableCell>{{ customer.contact_person }}</TableCell>
                            <TableCell>{{ customer.email }}</TableCell>
                            <TableCell>{{ customer.phone_number }}</TableCell>
                            <TableCell>{{ customer.kvk_number }}</TableCell>
                            <TableCell>{{ customer.city }}</TableCell>
                            <TableCell>{{ customer.registered_at }}</TableCell>
                            <TableCell>
                                <Button
                                    size="sm"
                                    @click="openApprovalDialog(customer.id)"
                                >
                                    Goedkeuren
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Approval Dialog -->
        <Dialog v-model:open="approvalDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Klant Goedkeuren</DialogTitle>
                    <DialogDescription>
                        Selecteer de klantcategorie voordat je de klant goedkeurt.
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4">
                    <Label class="mb-3 block">Klantcategorie *</Label>
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="groothandel"
                                value="groothandel"
                                v-model="selectedCategory"
                                class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                            />
                            <Label for="groothandel" class="cursor-pointer font-normal">
                                Groothandel
                            </Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="broodjeszaak"
                                value="broodjeszaak"
                                v-model="selectedCategory"
                                class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                            />
                            <Label for="broodjeszaak" class="cursor-pointer font-normal">
                                Broodjeszaak
                            </Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="horeca"
                                value="horeca"
                                v-model="selectedCategory"
                                class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                            />
                            <Label for="horeca" class="cursor-pointer font-normal">
                                Horeca
                            </Label>
                        </div>
                    </div>

                    <Label class="mb-3 block mt-4">Kortingspercentage *</Label>
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="discount_0"
                                value="0"
                                v-model="selectedDiscount"
                                class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                            />
                            <Label for="discount_0" class="cursor-pointer font-normal">
                                0% (geen korting)
                            </Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="discount_2_5"
                                value="2.5"
                                v-model="selectedDiscount"
                                class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                            />
                            <Label for="discount_2_5" class="cursor-pointer font-normal">
                                2,5%
                            </Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="discount_3"
                                value="3"
                                v-model="selectedDiscount"
                                class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                            />
                            <Label for="discount_3" class="cursor-pointer font-normal">
                                3%
                            </Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="discount_5"
                                value="5"
                                v-model="selectedDiscount"
                                class="h-4 w-4 border-gray-300 text-primary focus:ring-primary"
                            />
                            <Label for="discount_5" class="cursor-pointer font-normal">
                                5%
                            </Label>
                        </div>
                    </div>

                    <Label for="delivery_day" class="mb-2 block mt-4">Leverdag *</Label>
                    <select
                        id="delivery_day"
                        v-model="selectedDeliveryDay"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                        <option value="" disabled>Selecteer een dag</option>
                        <option v-for="day in deliveryDays" :key="day.value" :value="day.value">
                            {{ day.label }}
                        </option>
                    </select>
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="approvalDialogOpen = false"
                        :disabled="processing"
                    >
                        Annuleren
                    </Button>
                    <Button
                        @click="approveCustomer"
                        :disabled="!selectedCategory || !selectedDiscount || !selectedDeliveryDay || processing"
                    >
                        Goedkeuren
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
