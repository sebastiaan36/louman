<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { CalendarOff, PhoneCall, ShoppingCart } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
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
        <Link
            v-if="openOrdersCount > 0"
            :href="admin.orders.index.url({ query: { search: companyName } })"
            draggable="false"
            :title="`${openOrdersCount === 1 ? 'Eén bestelling' : openOrdersCount + ' bestellingen'} nog niet voltooid`"
        >
            <Badge class="bg-amber-500 text-white hover:bg-amber-600">
                {{ openOrdersCount === 1 ? 'Open bestelling' : `${openOrdersCount} open bestellingen` }}
            </Badge>
        </Link>

        <Button
            size="sm"
            :variant="routeFlag === 'callback' ? 'default' : 'outline'"
            :disabled="saving"
            :aria-pressed="routeFlag === 'callback'"
            title="Klant wil teruggebeld worden"
            draggable="false"
            @click="toggleFlag('callback')"
        >
            <PhoneCall class="h-4 w-4 mr-2" />
            Terugbellen
        </Button>

        <Button
            size="sm"
            :variant="routeFlag === 'skip_week' ? 'default' : 'outline'"
            :disabled="saving"
            :aria-pressed="routeFlag === 'skip_week'"
            title="Klant hoeft deze week niet te bestellen"
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
            <Button size="sm" variant="outline">
                <ShoppingCart class="h-4 w-4 mr-2" />
                Bestelling maken
            </Button>
        </Link>
    </div>
</template>
