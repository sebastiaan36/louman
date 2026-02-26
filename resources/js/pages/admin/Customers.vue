<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
    city: string;
    approved_at: string;
}

defineProps<{
    customers: Customer[];
}>();

const page = usePage();
const importResults = computed(
    () => (page.props.flash as Record<string, unknown>)?.import_results as { updated: number; skipped: string[] } | null,
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Klanten',
    },
];

const viewCustomer = (customerId: number) => {
    router.visit(`/admin/customers/${customerId}`);
};

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
    router.post('/admin/customers/import', formData, {
        onFinish: () => {
            importing.value = false;
            importOpen.value = false;
            csvFile.value = null;
        },
    });
};
</script>

<template>
    <Head title="Klanten" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <Users class="h-6 w-6" />
                        Klanten
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Overzicht van alle goedgekeurde klanten
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="/admin/customers/export">
                        <Button variant="outline">CSV downloaden</Button>
                    </a>
                    <Button variant="outline" @click="importOpen = true">CSV importeren</Button>
                </div>
            </div>

            <!-- Import Results -->
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

            <div v-if="customers.length === 0" class="rounded-lg border border-dashed p-12 text-center">
                <p class="text-muted-foreground">Geen klanten gevonden</p>
            </div>

            <div v-else class="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Bedrijfsnaam</TableHead>
                            <TableHead>Contactpersoon</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Telefoon</TableHead>
                            <TableHead>Plaats</TableHead>
                            <TableHead>Goedgekeurd op</TableHead>
                            <TableHead></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="customer in customers"
                            :key="customer.id"
                            class="cursor-pointer hover:bg-muted/50"
                            @click="viewCustomer(customer.id)"
                        >
                            <TableCell class="font-medium">{{ customer.company_name }}</TableCell>
                            <TableCell>{{ customer.contact_person }}</TableCell>
                            <TableCell>{{ customer.email }}</TableCell>
                            <TableCell>{{ customer.phone_number }}</TableCell>
                            <TableCell>{{ customer.city }}</TableCell>
                            <TableCell>{{ customer.approved_at }}</TableCell>
                            <TableCell class="text-right">
                                <Button size="sm" variant="outline" @click.stop="viewCustomer(customer.id)">
                                    Bekijk & bewerk
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Import Dialog -->
        <Dialog :open="importOpen" @update:open="importOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Klanten importeren via CSV</DialogTitle>
                    <DialogDescription>
                        Upload een CSV-bestand om bestaande klanten bij te werken. Klanten worden geïdentificeerd via de <strong>id</strong> kolom.
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
                            id, company_name, contact_person, phone_number, street_name, house_number,
                            postal_code, city, packing_slip_email, customer_category, discount_percentage,
                            delivery_day, route_order, show_on_map
                        </p>
                        <p class="mt-2">
                            Alleen kolommen die aanwezig zijn in het bestand worden bijgewerkt. Lege cellen worden overgeslagen.
                            Alleen bestaande klanten worden bijgewerkt op basis van <code class="font-mono">id</code> — nieuwe klanten worden niet aangemaakt.
                        </p>
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
    </AppLayout>
</template>
