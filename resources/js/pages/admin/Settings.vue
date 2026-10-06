<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Mail, Tags, Trash2 } from 'lucide-vue-next';
import CustomerCategoryController from '@/actions/App/Http/Controllers/Admin/CustomerCategoryController';
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
    mail_registration_cc: string | null;
    mail_cancellation_notification: string | null;
    mail_support_notification: string | null;
    support_phone: string | null;
    support_email: string | null;
    mail_cc: string | null;
    mail_reply_to: string | null;
}

interface CustomerCategory {
    id: number;
    name: string;
    slug: string;
    customers_count: number;
}

const props = defineProps<{
    settings: Settings;
    customerCategories: CustomerCategory[];
    customersWithoutCategoryCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Instellingen' },
];

const form = useForm({
    mail_order_notification: props.settings.mail_order_notification ?? '',
    mail_registration_notification: props.settings.mail_registration_notification ?? '',
    mail_registration_cc: props.settings.mail_registration_cc ?? '',
    mail_cancellation_notification: props.settings.mail_cancellation_notification ?? '',
    mail_support_notification: props.settings.mail_support_notification ?? '',
    support_phone: props.settings.support_phone ?? '',
    support_email: props.settings.support_email ?? '',
    mail_cc: props.settings.mail_cc ?? '',
    mail_reply_to: props.settings.mail_reply_to ?? '',
});

const submit = () => {
    form.patch('/admin/settings', { preserveScroll: true });
};

const categoryForm = useForm({ name: '' });

const addCategory = () => {
    categoryForm.submit(CustomerCategoryController.store(), {
        preserveScroll: true,
        onSuccess: () => categoryForm.reset(),
    });
};

const deleteCategory = (category: CustomerCategory) => {
    if (!confirm(`Klantcategorie ${category.name} verwijderen?`)) {
        return;
    }

    router.visit(CustomerCategoryController.destroy(category.id), { preserveScroll: true });
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
                        <Label for="mail_registration_cc">Nieuwe registratie — CC</Label>
                        <Input
                            id="mail_registration_cc"
                            v-model="form.mail_registration_cc"
                            type="email"
                            placeholder="Leeg = geen CC"
                        />
                        <p class="text-xs text-muted-foreground">
                            Adres dat een kopie krijgt bij een nieuwe registratie en zodra een genodigde
                            klant zijn account aanmaakt. Alleen bij die twee momenten, niet bij alle mails.
                        </p>
                        <InputError :message="form.errors.mail_registration_cc" />
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

                    <div class="grid gap-2">
                        <Label for="mail_support_notification">Vraag via het klantportaal</Label>
                        <Input
                            id="mail_support_notification"
                            v-model="form.mail_support_notification"
                            type="email"
                            placeholder="Leeg = alle beheerders"
                        />
                        <p class="text-xs text-muted-foreground">
                            Adres dat de vragen ontvangt die klanten via de hulpknop stellen. Laat leeg om alle beheerders te mailen.
                        </p>
                        <InputError :message="form.errors.mail_support_notification" />
                    </div>
                </div>

                <div class="rounded-lg border p-6 space-y-5">
                    <h2 class="text-lg font-semibold">Hulpknop</h2>

                    <div class="grid gap-2">
                        <Label for="support_phone">Telefoonnummer</Label>
                        <Input
                            id="support_phone"
                            v-model="form.support_phone"
                            type="text"
                            placeholder="Leeg = geen telefoonnummer tonen"
                        />
                        <p class="text-xs text-muted-foreground">
                            Nummer boven het contactformulier in het klantportaal. Op mobiel kan de klant
                            er direct op bellen. Laat leeg om alleen het formulier te tonen.
                        </p>
                        <InputError :message="form.errors.support_phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="support_email">E-mailadres</Label>
                        <Input
                            id="support_email"
                            v-model="form.support_email"
                            type="email"
                            placeholder="Leeg = geen e-mailadres tonen"
                        />
                        <p class="text-xs text-muted-foreground">
                            Adres dat de klant in de hulpknop ziet staan en direct kan aanklikken. Dit
                            is wat de klant ziet; waar het formulier zelf naartoe gaat, stel je hierboven in.
                        </p>
                        <InputError :message="form.errors.support_email" />
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

                    <div class="grid gap-2">
                        <Label for="mail_reply_to">Antwoord-aan adres</Label>
                        <Input
                            id="mail_reply_to"
                            v-model="form.mail_reply_to"
                            type="email"
                            placeholder="Leeg = geen antwoord-aan"
                        />
                        <p class="text-xs text-muted-foreground">
                            Antwoordt een klant op een mail van het portaal, dan komt het antwoord op
                            dit adres binnen in plaats van bij de afzender.
                        </p>
                        <InputError :message="form.errors.mail_reply_to" />
                    </div>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        Opslaan
                    </Button>
                </div>
            </form>

            <section class="max-w-xl rounded-lg border p-6 space-y-5">
                <div>
                    <h2 class="text-lg font-semibold flex items-center gap-2">
                        <Tags class="h-5 w-5" />
                        Klantcategorieën
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        De categorieën die je bij een klant kunt kiezen onder Klantinstellingen.
                    </p>
                </div>

                <ul class="divide-y rounded-md border">
                    <li
                        v-for="category in customerCategories"
                        :key="category.id"
                        class="flex items-center justify-between gap-3 px-4 py-2"
                    >
                        <span class="text-sm font-medium">{{ category.name }}</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-muted-foreground">
                                {{ category.customers_count }} {{ category.customers_count === 1 ? 'klant' : 'klanten' }}
                            </span>
                            <Button
                                v-if="category.customers_count === 0"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-muted-foreground hover:text-destructive"
                                :title="`${category.name} verwijderen`"
                                @click="deleteCategory(category)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </li>
                    <li
                        v-if="customersWithoutCategoryCount > 0"
                        class="flex items-center justify-between gap-3 px-4 py-2"
                    >
                        <span class="text-sm italic text-muted-foreground">Geen categorie</span>
                        <span class="text-sm text-muted-foreground">
                            {{ customersWithoutCategoryCount }} {{ customersWithoutCategoryCount === 1 ? 'klant' : 'klanten' }}
                        </span>
                    </li>
                </ul>

                <form class="grid gap-2" @submit.prevent="addCategory">
                    <Label for="customer_category_name">Nieuwe categorie</Label>
                    <div class="flex gap-2">
                        <Input
                            id="customer_category_name"
                            v-model="categoryForm.name"
                            type="text"
                            maxlength="50"
                            placeholder="Bijv. Detailhandel"
                        />
                        <Button type="submit" :disabled="categoryForm.processing || !categoryForm.name.trim()">
                            Toevoegen
                        </Button>
                    </div>
                    <InputError :message="categoryForm.errors.name" />
                </form>
            </section>
        </div>
    </AppLayout>
</template>
