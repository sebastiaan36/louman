<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Mail } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface Settings {
    mail_order_notification: string | null;
    mail_registration_notification: string | null;
    mail_cancellation_notification: string | null;
    mail_cc: string | null;
}

const props = defineProps<{
    settings: Settings;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Instellingen' },
];

const form = useForm({
    mail_order_notification: props.settings.mail_order_notification ?? '',
    mail_registration_notification: props.settings.mail_registration_notification ?? '',
    mail_cancellation_notification: props.settings.mail_cancellation_notification ?? '',
    mail_cc: props.settings.mail_cc ?? '',
});

const submit = () => {
    form.patch('/admin/settings', { preserveScroll: true });
};
</script>

<template>
    <Head title="Instellingen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <Mail class="h-6 w-6" />
                    E-mailinstellingen
                </h1>
                <p class="text-sm text-muted-foreground">
                    Stel in waar de verschillende notificaties naartoe gestuurd worden.
                </p>
            </div>

            <div
                v-if="form.recentlySuccessful"
                class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800"
            >
                Instellingen opgeslagen.
            </div>

            <form class="max-w-xl space-y-6" @submit.prevent="submit">
                <div class="rounded-lg border p-6 space-y-5">
                    <h2 class="text-lg font-semibold">Notificatie-ontvangers</h2>

                    <div class="grid gap-2">
                        <Label for="mail_order_notification">Nieuwe bestelling</Label>
                        <Input
                            id="mail_order_notification"
                            v-model="form.mail_order_notification"
                            type="email"
                            placeholder="info@louman-jordaan.nl"
                        />
                        <p class="text-xs text-muted-foreground">
                            Adres dat een melding krijgt zodra een klant een bestelling plaatst.
                        </p>
                        <InputError :message="form.errors.mail_order_notification" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="mail_registration_notification">Nieuwe registratie</Label>
                        <Input
                            id="mail_registration_notification"
                            v-model="form.mail_registration_notification"
                            type="email"
                            placeholder="Leeg = alle beheerders"
                        />
                        <p class="text-xs text-muted-foreground">
                            Adres dat een melding krijgt bij een nieuwe klantregistratie. Laat leeg om alle beheerders te mailen.
                        </p>
                        <InputError :message="form.errors.mail_registration_notification" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="mail_cancellation_notification">Geannuleerde bestelling</Label>
                        <Input
                            id="mail_cancellation_notification"
                            v-model="form.mail_cancellation_notification"
                            type="email"
                            placeholder="Leeg = geen melding"
                        />
                        <p class="text-xs text-muted-foreground">
                            Adres dat een melding krijgt zodra een bestelling wordt geannuleerd. Laat leeg om geen melding te versturen.
                        </p>
                        <InputError :message="form.errors.mail_cancellation_notification" />
                    </div>
                </div>

                <div class="rounded-lg border p-6 space-y-5">
                    <h2 class="text-lg font-semibold">CC</h2>

                    <div class="grid gap-2">
                        <Label for="mail_cc">CC-adres</Label>
                        <Input
                            id="mail_cc"
                            v-model="form.mail_cc"
                            type="email"
                            placeholder="Leeg = geen CC"
                        />
                        <p class="text-xs text-muted-foreground">
                            Dit adres ontvangt een kopie (CC) van alle uitgaande mails.
                        </p>
                        <InputError :message="form.errors.mail_cc" />
                    </div>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        Opslaan
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
