<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';

defineProps<{
    token: string;
    email: string;
    companyName: string;
}>();
</script>

<template>
    <AuthBase
        title="Account aanmaken"
        :description="`Welkom ${companyName}. Stel een wachtwoord in om je account te activeren.`"
    >
        <Head title="Account aanmaken" />

        <Form
            :action="`/invitation/${token}`"
            method="post"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-2">
                <Label>E-mailadres</Label>
                <Input :value="email" type="email" disabled readonly />
            </div>

            <div class="grid gap-2">
                <Label for="password">Wachtwoord</Label>
                <Input
                    id="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    name="password"
                    placeholder="Wachtwoord"
                    autofocus
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

            <Button type="submit" class="w-full" :disabled="processing">
                <Spinner v-if="processing" />
                Account activeren
            </Button>
        </Form>
    </AuthBase>
</template>
