<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { CalendarOff, CheckCircle2, PhoneCall, ShoppingCart } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import admin from '@/routes/admin';

export type RouteFlag = 'callback' | 'skip_week' | null;

const props = defineProps<{
    customerId: number;
    companyName: string;
    openOrdersCount: number;
    routeFlag: RouteFlag;
}>();

const saving = ref(false);

// Clicking the active marker clears it; clicking the other one replaces it.
const toggleFlag = (flag: Exclude<RouteFlag, null>) => {
    saving.value = true;
    router.patch(
        `/admin/delivery-route/${props.customerId}/flag`,
        { flag: props.routeFlag === flag ? null : flag },
        {
            preserveScroll: true,
            onFinish: () => {
                saving.value = false;
            },
        },
    );
};
</script>

<template>
    <div class="ml-auto flex shrink-0 flex-wrap items-center justify-end gap-2" draggable="false">
        <!-- Heeft besteld: groen, in dezelfde knopstijl als de markeringen. -->
        <Link
            v-if="openOrdersCount > 0"
            :href="admin.orders.index.url({ query: { search: companyName } })"
            draggable="false"
            :title="`${openOrdersCount === 1 ? 'Eén bestelling' : openOrdersCount + ' bestellingen'} nog niet voltooid`"
        >
            <Button size="sm" variant="outline" class="border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-700 hover:text-white">
                <CheckCircle2 class="h-4 w-4 mr-2" />
                {{ openOrdersCount === 1 ? 'Heeft besteld' : `Heeft ${openOrdersCount}× besteld` }}
            </Button>
        </Link>

        <!-- Terugbellen: oranje zodra de markering aan staat. -->
        <Button
            size="sm"
            variant="outline"
            :class="routeFlag === 'callback' ? 'border-orange-500 bg-orange-500 text-white hover:bg-orange-600 hover:text-white' : ''"
            :disabled="saving"
            :aria-pressed="routeFlag === 'callback'"
            title="Klant wil teruggebeld worden (reset zondag 21:00)"
            draggable="false"
            @click="toggleFlag('callback')"
        >
            <PhoneCall class="h-4 w-4 mr-2" />
            Terugbellen
        </Button>

        <!-- Niet deze week: rood zodra de markering aan staat. -->
        <Button
            size="sm"
            variant="outline"
            :class="routeFlag === 'skip_week' ? 'border-red-600 bg-red-600 text-white hover:bg-red-700 hover:text-white' : ''"
            :disabled="saving"
            :aria-pressed="routeFlag === 'skip_week'"
            title="Klant hoeft deze week niet te bestellen (reset zondag 21:00)"
            draggable="false"
            @click="toggleFlag('skip_week')"
        >
            <CalendarOff class="h-4 w-4 mr-2" />
            Niet deze week
        </Button>

        <Link
            :href="admin.orders.create.url({ query: { customer: customerId } })"
            draggable="false"
        >
            <!-- Wit zodra de klant al besteld heeft of deze week niet hoeft te bestellen;
                 groen alleen als er nog een bestelling verwacht wordt. -->
            <Button size="sm" :variant="openOrdersCount > 0 || routeFlag === 'skip_week' ? 'outline' : 'default'">
                <ShoppingCart class="h-4 w-4 mr-2" />
                Bestelling maken
            </Button>
        </Link>
    </div>
</template>
