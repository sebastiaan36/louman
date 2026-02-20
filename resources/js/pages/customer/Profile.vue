<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface Customer {
    company_name: string;
    contact_person: string;
    phone_number: string;
    street_name: string;
    house_number: string;
    postal_code: string;
    city: string;
    kvk_number: string;
    bank_account: string;
    vat_number: string;
    packing_slip_email: string | null;
}

const props = defineProps<{
    customer: Customer;
    email: string;
    errors?: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Profiel',
    },
];

const form = ref({
    company_name: props.customer.company_name,
    email: props.email,
    kvk_number: props.customer.kvk_number,
    vat_number: props.customer.vat_number,
    contact_person: props.customer.contact_person,
    phone_number: props.customer.phone_number,
    street_name: props.customer.street_name,
    house_number: props.customer.house_number,
    postal_code: props.customer.postal_code,
    city: props.customer.city,
    bank_account: props.customer.bank_account,
    packing_slip_email: props.customer.packing_slip_email,
});

const processing = ref(false);

const submit = () => {
    processing.value = true;
    router.patch('/customer/profile', form.value, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <Head title="Profiel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-bold">Mijn Profiel</h1>
                <p class="text-sm text-muted-foreground">
                    Beheer je bedrijfsgegevens
                </p>
            </div>

            <form @submit.prevent="submit" class="max-w-2xl space-y-6">
                <!-- Editable Company Section -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Bedrijfsgegevens</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="company_name">Bedrijfsnaam</Label>
                            <Input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                required
                            />
                            <InputError :message="errors?.company_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                            />
                            <InputError :message="errors?.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="kvk_number">KvK nummer</Label>
                            <Input
                                id="kvk_number"
                                v-model="form.kvk_number"
                                type="text"
                                required
                            />
                            <InputError :message="errors?.kvk_number" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="vat_number">BTW nummer</Label>
                            <Input
                                id="vat_number"
                                v-model="form.vat_number"
                                type="text"
                                required
                            />
                            <InputError :message="errors?.vat_number" />
                        </div>
                    </div>
                </div>

                <!-- Editable Contact Section -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Contactgegevens</h3>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="contact_person">Contactpersoon</Label>
                            <Input
                                id="contact_person"
                                v-model="form.contact_person"
                                type="text"
                                required
                            />
                            <InputError :message="errors?.contact_person" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone_number">Telefoonnummer</Label>
                            <Input
                                id="phone_number"
                                v-model="form.phone_number"
                                type="tel"
                                required
                            />
                            <InputError :message="errors?.phone_number" />
                        </div>
                    </div>
                </div>

                <!-- Email Preferences Section -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">E-mail voorkeuren</h3>
                    <p class="text-sm text-muted-foreground">
                        Optioneel: specificeer een afwijkend e-mailadres voor pakbonnen
                    </p>

                    <div class="grid gap-2">
                        <Label for="packing_slip_email">Pakbon e-mailadres</Label>
                        <Input
                            id="packing_slip_email"
                            v-model="form.packing_slip_email"
                            type="email"
                            placeholder="Optioneel"
                        />
                        <InputError :message="errors?.packing_slip_email" />
                    </div>
                </div>

                <!-- Editable Address Section -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Adresgegevens</h3>

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
                </div>

                <!-- Banking Section -->
                <div class="rounded-lg border p-6 space-y-4">
                    <h3 class="text-sm font-semibold">Bankgegevens</h3>

                    <div class="grid gap-2">
                        <Label for="bank_account">Rekeningnummer (IBAN)</Label>
                        <Input
                            id="bank_account"
                            v-model="form.bank_account"
                            type="text"
                            required
                        />
                        <InputError :message="errors?.bank_account" />
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button type="submit" :disabled="processing">
                        Opslaan
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
