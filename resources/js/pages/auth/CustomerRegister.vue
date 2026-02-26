<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
</script>

<template>
    <AuthBase
        title="B2B Klantregistratie"
        description="Registreer je bedrijf voor toegang tot het klantportaal"
    >
        <Head title="Klantregistratie" />

        <Form
            action="/register/customer"
            method="post"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <!-- Contact Section -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-foreground">Contactgegevens</h3>

                    <div class="grid gap-2">
                        <Label for="company_name">Bedrijfsnaam</Label>
                        <Input
                            id="company_name"
                            type="text"
                            required
                            autofocus
                            name="company_name"
                            placeholder="Bedrijfsnaam"
                        />
                        <InputError :message="errors.company_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="contact_person">Contactpersoon</Label>
                        <Input
                            id="contact_person"
                            type="text"
                            required
                            name="contact_person"
                            placeholder="Voor- en achternaam"
                        />
                        <InputError :message="errors.contact_person" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">E-mailadres</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autocomplete="email"
                            name="email"
                            placeholder="email@bedrijf.nl"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone_number">Telefoonnummer</Label>
                        <Input
                            id="phone_number"
                            type="tel"
                            required
                            name="phone_number"
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
                            placeholder="NL123456789B01"
                        />
                        <InputError :message="errors.vat_number" />
                    </div>
                </div>

                <!-- Password Section -->
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-foreground">Wachtwoord</h3>

                    <div class="grid gap-2">
                        <Label for="password">Wachtwoord</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            autocomplete="new-password"
                            name="password"
                            placeholder="Wachtwoord"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Bevestig wachtwoord</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="Bevestig wachtwoord"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>
                </div>

                <!-- Kaart sectie -->
                <div class="flex items-start gap-3">
                    <Checkbox
                        id="show_on_map"
                        name="show_on_map"
                        :default-checked="true"
                        value="1"
                    />
                    <div class="grid gap-1">
                        <Label for="show_on_map" class="leading-snug cursor-pointer">
                            Toon mijn bedrijf op de kaart op louman-jordaan.nl
                        </Label>
                    </div>
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" />
                    Registreren
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Heb je al een account?
                <TextLink :href="login()">
                    Inloggen
                </TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
