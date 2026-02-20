<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { LogIn, UserPlus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Welkom" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 p-6">
        <div class="w-full max-w-5xl space-y-8">
            <!-- Logo -->
            <div class="flex justify-center">
                <img
                    src="/storage/img/Logo.png"
                    alt="Louman Logo"
                    class="w-full max-w-[250px]"
                />
            </div>

            <!-- Title -->
            <div class="text-center">
                <h1 class="text-4xl font-bold text-slate-900">B2B bestelomgeving</h1>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Existing Customers -->
                <Card class="shadow-lg hover:shadow-xl transition-shadow">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-2xl">
                            <LogIn class="h-6 w-6" />
                            Bestaande klanten
                        </CardTitle>
                        <CardDescription class="text-base">
                            Log in om uw bestelling te plaatsen
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <p class="text-sm text-muted-foreground">
                            Heeft u al een account? Log in om toegang te krijgen tot uw persoonlijke bestelomgeving.
                        </p>
                        <Link href="/login">
                            <Button size="lg" class="w-full">
                                <LogIn class="h-5 w-5 mr-2" />
                                Inloggen
                            </Button>
                        </Link>
                    </CardContent>
                </Card>

                <!-- New Customers -->
                <Card v-if="canRegister" class="shadow-lg hover:shadow-xl transition-shadow">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-2xl">
                            <UserPlus class="h-6 w-6" />
                            Nieuwe klanten
                        </CardTitle>
                        <CardDescription class="text-base">
                            Registreer voor een zakelijk account
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <p class="text-sm text-muted-foreground">
                            Nog geen account? Registreer uw bedrijf om toegang te krijgen tot onze B2B bestelomgeving.
                        </p>
                        <Link href="/register/customer">
                            <Button size="lg" variant="outline" class="w-full">
                                <UserPlus class="h-5 w-5 mr-2" />
                                Registreren
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <!-- Footer -->
            <div class="text-center text-sm text-muted-foreground pt-8">
                <p>&copy; {{ new Date().getFullYear() }} Louman Jordaan. Alle rechten voorbehouden.</p>
            </div>
        </div>
    </div>
</template>
