<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Users } from 'lucide-vue-next';

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
</script>

<template>
    <Head title="Klanten" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <Users class="h-6 w-6" />
                    Klanten
                </h1>
                <p class="text-sm text-muted-foreground">
                    Overzicht van alle goedgekeurde klanten
                </p>
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
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
