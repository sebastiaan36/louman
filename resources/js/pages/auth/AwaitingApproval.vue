<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AuthBase from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthBase
        title="Account in afwachting"
        description="Je account moet nog worden goedgekeurd"
    >
        <Head title="Wacht op goedkeuring" />

        <div class="flex flex-col gap-6 text-center">
            <div class="rounded-lg bg-muted/50 p-6">
                <svg
                    class="mx-auto h-12 w-12 text-muted-foreground"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>

                <h3 class="mt-4 text-sm font-semibold">Bedankt voor je registratie!</h3>

                <div class="mt-2 space-y-2 text-sm text-muted-foreground">
                    <p v-if="status" class="text-green-600 dark:text-green-500">
                        {{ status }}
                    </p>
                    <p>Je account wacht op goedkeuring door onze medewerkers.</p>
                    <p>Je ontvangt een email zodra je account is goedgekeurd.</p>
                    <p>Controleer je inbox voor de email verificatie link.</p>
                </div>
            </div>

            <form :action="logout.url()" method="POST">
                <Button type="submit" variant="outline" class="w-full">
                    Uitloggen
                </Button>
            </form>
        </div>
    </AuthBase>
</template>
