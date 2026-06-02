<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';

interface CustomerPrefill {
    company_name: string;
    contact_person: string | null;
    phone_number: string | null;
    street_name: string | null;
    house_number: string | null;
    postal_code: string | null;
    city: string | null;
    kvk_number: string | null;
    bank_account: string | null;
    vat_number: string | null;
    show_on_map: boolean | null;
}

defineProps<{
    customer: CustomerPrefill;
}>();
</script>

<template>
    <AuthBase
        title="Vul je gegevens aan"
        description="Vul de onderstaande bedrijfsgegevens aan om je registratie af te ronden."
    >
        <Head title="Gegevens aanvullen" />

        <Form
            action="/customer/complete-profile"
            method="patch"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <!-- Contact Section -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-foreground">Contactgegevens</h3>

                    <div class="grid gap-2">
                        <Label>Bedrijfsnaam</Label>
                        <Input :value="customer.company_name" type="text" disabled readonly />
                    </div>

                    <div class="grid gap-2">
                        <Label for="contact_person">Contactpersoon</Label>
                        <Input
                            id="contact_person"
                            type="text"
                            required
                            autofocus
                            name="contact_person"
                            :default-value="customer.contact_person ?? ''"
                            placeholder="Voor- en achternaam"
                        />
                        <InputError :message="errors.contact_person" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone_number">Telefoonnummer</Label>
                        <Input
                            id="phone_number"
                            type="tel"
                            required
                            name="phone_number"
                            :default-value="customer.phone_number ?? ''"
                            placeholder="06-12345678"
                        />
                        <InputError :message="errors.phone_number" />
                    </div>
                </div>

                <!-- Address Section -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-foreground">Adresgegevens</h3>

                    <div class="grid gap-2">
                        <Label for="street_name">Straatnaam</Label>
                        <Input
                            id="street_name"
                            type="text"
                            required
                            name="street_name"
                            :default-value="customer.street_name ?? ''"
                            placeholder="Straatnaam"
                        />
                        <InputError :message="errors.street_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="house_number">Huisnummer</Label>
                        <Input
                            id="house_number"
                            type="text"
                            required
                            name="house_number"
                            :default-value="customer.house_number ?? ''"
                            placeholder="123"
                        />
                        <InputError :message="errors.house_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="postal_code">Postcode</Label>
                        <Input
                            id="postal_code"
                            type="text"
                            required
                            name="postal_code"
                            :default-value="customer.postal_code ?? ''"
                            placeholder="1234 AB"
                        />
                        <InputError :message="errors.postal_code" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="city">Plaats</Label>
                        <Input
                            id="city"
                            type="text"
                            required
                            name="city"
                            :default-value="customer.city ?? ''"
                            placeholder="Plaats"
                        />
                        <InputError :message="errors.city" />
                    </div>
                </div>

                <!-- Company Details Section -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-foreground">Bedrijfsgegevens</h3>

                    <div class="grid gap-2">
                        <Label for="kvk_number">KvK nummer</Label>
                        <Input
                            id="kvk_number"
                            type="text"
                            required
                            name="kvk_number"
                            :default-value="customer.kvk_number ?? ''"
                            placeholder="12345678"
                            maxlength="8"
                        />
                        <InputError :message="errors.kvk_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="bank_account">Rekeningnummer (IBAN)</Label>
                        <Input
                            id="bank_account"
                            type="text"
                            required
                            name="bank_account"
                            :default-value="customer.bank_account ?? ''"
                            placeholder="NL12 ABCD 0123 4567 89"
                        />
                        <InputError :message="errors.bank_account" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="vat_number">BTW nummer</Label>
                        <Input
                            id="vat_number"
                            type="text"
                            required
                            name="vat_number"
                            :default-value="customer.vat_number ?? ''"
                            placeholder="NL123456789B01"
                        />
                        <InputError :message="errors.vat_number" />
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <Checkbox
                        id="show_on_map"
                        name="show_on_map"
                        :default-checked="customer.show_on_map ?? true"
                        value="1"
                    />
                    <div class="grid gap-1">
                        <Label for="show_on_map" class="leading-snug cursor-pointer">
                            Toon mijn bedrijf op de kaart op louman-jordaan.nl
                        </Label>
                    </div>
                </div>

                <Button type="submit" class="mt-2 w-full" :disabled="processing">
                    <Spinner v-if="processing" />
                    Gegevens opslaan
                </Button>
            </div>
        </Form>
    </AuthBase>
</template>
