<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
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
}

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    customer?: Customer;
};

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profielinstellingen',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;
const isCustomer = user.role === 'customer' && props.customer;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profielinstellingen" />

        <h1 class="sr-only">Profielinstellingen</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Profielinformatie"
                    :description="isCustomer ? 'Werk je profiel en bedrijfsgegevens bij' : 'Werk je naam en e-mailadres bij'"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <!-- User fields -->
                    <div class="grid gap-2">
                        <Label for="name">Naam</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Volledige naam"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">E-mailadres</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="E-mailadres"
                            readonly
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <!-- Customer fields (only if user is a customer) -->
                    <template v-if="isCustomer && customer">
                        <div class="border-t pt-6 mt-6">
                            <h3 class="text-sm font-semibold mb-4">Bedrijfsgegevens</h3>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="company_name">Bedrijfsnaam</Label>
                                    <Input
                                        id="company_name"
                                        name="company_name"
                                        :default-value="customer.company_name"
                                        required
                                    />
                                    <InputError class="mt-2" :message="errors.company_name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="kvk_number">KvK nummer</Label>
                                    <Input
                                        id="kvk_number"
                                        name="kvk_number"
                                        :default-value="customer.kvk_number"
                                        required
                                        readonly
                                    />
                                    <InputError class="mt-2" :message="errors.kvk_number" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="vat_number">BTW nummer</Label>
                                    <Input
                                        id="vat_number"
                                        name="vat_number"
                                        :default-value="customer.vat_number"
                                        required
                                    />
                                    <InputError class="mt-2" :message="errors.vat_number" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="contact_person">Contactpersoon</Label>
                                    <Input
                                        id="contact_person"
                                        name="contact_person"
                                        :default-value="customer.contact_person"
                                        required
                                    />
                                    <InputError class="mt-2" :message="errors.contact_person" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="phone_number">Telefoonnummer</Label>
                                    <Input
                                        id="phone_number"
                                        name="phone_number"
                                        type="tel"
                                        :default-value="customer.phone_number"
                                        required
                                    />
                                    <InputError class="mt-2" :message="errors.phone_number" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-sm font-semibold mb-4">Adresgegevens</h3>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="street_name">Straatnaam</Label>
                                    <Input
                                        id="street_name"
                                        name="street_name"
                                        :default-value="customer.street_name"
                                        required
                                    />
                                    <InputError class="mt-2" :message="errors.street_name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="house_number">Huisnummer</Label>
                                    <Input
                                        id="house_number"
                                        name="house_number"
                                        :default-value="customer.house_number"
                                        required
                                    />
                                    <InputError class="mt-2" :message="errors.house_number" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="postal_code">Postcode</Label>
                                    <Input
                                        id="postal_code"
                                        name="postal_code"
                                        :default-value="customer.postal_code"
                                        required
                                    />
                                    <InputError class="mt-2" :message="errors.postal_code" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="city">Plaats</Label>
                                    <Input
                                        id="city"
                                        name="city"
                                        :default-value="customer.city"
                                        required
                                    />
                                    <InputError class="mt-2" :message="errors.city" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-sm font-semibold mb-4">Bankgegevens</h3>

                            <div class="grid gap-2">
                                <Label for="bank_account">Rekeningnummer (IBAN)</Label>
                                <Input
                                    id="bank_account"
                                    name="bank_account"
                                    :default-value="customer.bank_account"
                                    required
                                />
                                <InputError class="mt-2" :message="errors.bank_account" />
                            </div>
                        </div>
                    </template>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Je e-mailadres is niet geverifieerd.
                            <button
                                type="button"
                                @click="router.post('/email/verification-notification')"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Klik hier om de verificatie-e-mail opnieuw te versturen.
                            </button>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            Een nieuwe verificatielink is naar je e-mailadres gestuurd.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >Opslaan</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Opgeslagen.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
