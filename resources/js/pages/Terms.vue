<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { home } from '@/routes';
</script>

<template>
    <Head title="Algemene Voorwaarden" />

    <div class="min-h-screen bg-background py-12 px-6">
        <div class="mx-auto w-full max-w-3xl space-y-8">
            <div class="flex justify-center">
                <img src="/storage/img/Logo.png" alt="Louman Jordaan" class="w-full max-w-[180px]" />
            </div>

            <div class="space-y-2 text-center">
                <h1 class="text-3xl font-bold text-foreground">Algemene Voorwaarden</h1>
                <p class="text-sm text-muted-foreground">Worstmakerij Louman Jordaan</p>
            </div>

            <!--
                PLACEHOLDER — vervang de inhoud hieronder door de definitieve
                Algemene Voorwaarden. Alleen de tekst binnen dit <article>-blok
                hoeft aangepast te worden; de opmaak (prose) regelt de rest.
            -->
            <article class="prose prose-sm max-w-none text-foreground">
                <p class="rounded-md border border-dashed border-yellow-400 bg-yellow-50 p-4 text-yellow-800">
                    <strong>Placeholder.</strong> Dit is voorbeeldtekst. Plaats hier de
                    definitieve Algemene Voorwaarden (vervang dit hele blok).
                </p>

                <h2>1. Algemeen</h2>
                <p>
                    Deze algemene voorwaarden zijn van toepassing op alle bestellingen
                    en overeenkomsten via het B2B-klantportaal van Worstmakerij Louman
                    Jordaan.
                </p>

                <h2>2. Registratie en account</h2>
                <p>
                    Bij registratie verstrekt de klant juiste en volledige gegevens. Een
                    account wordt pas actief na goedkeuring door Louman Jordaan.
                </p>

                <h2>3. Bestellingen en levering</h2>
                <p>Voorbeeldtekst — nader in te vullen.</p>

                <h2>4. Prijzen en betaling</h2>
                <p>Voorbeeldtekst — nader in te vullen.</p>

                <h2>5. Privacy</h2>
                <p>Voorbeeldtekst — nader in te vullen.</p>
            </article>

            <div class="text-center">
                <Link :href="home()" class="text-sm text-muted-foreground underline underline-offset-4">
                    Terug naar de startpagina
                </Link>
            </div>
        </div>
    </div>
</template>
