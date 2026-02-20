<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ShoppingCart, Users, Euro, TrendingUp, TrendingDown, Clock, Zap, FileText, UsersRound, Package } from 'lucide-vue-next';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';

interface AdminStats {
    pendingOrders: number;
    currentMonthRevenue: string;
    revenueChangePercentage: number;
    revenueIncreased: boolean;
    pendingCustomers: number;
    ordersThisMonth: number;
    totalCustomers: number;
    activeProducts: number;
}

interface CustomerStats {
    openOrders: number;
    quickOrderProducts: number;
}

interface OrderDeadline {
    deadline: string;
    days: number;
    hours: number;
    minutes: number;
    total_hours: number;
    is_urgent: boolean;
}

defineProps<{
    stats?: AdminStats | CustomerStats;
    orderDeadline?: OrderDeadline;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.role === 'admin');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Admin Statistics -->
            <div v-if="isAdmin && stats" class="grid gap-4 md:grid-cols-3">
                <!-- Pending Orders Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Openstaande Bestellingen
                        </CardTitle>
                        <ShoppingCart class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pendingOrders }}</div>
                        <p class="text-xs text-muted-foreground">
                            <a :href="admin.orders.index().url" class="text-primary hover:underline">
                                Bekijk bestellingen
                            </a>
                        </p>
                    </CardContent>
                </Card>

                <!-- Monthly Revenue Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Omzet Deze Maand
                        </CardTitle>
                        <Euro class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">€{{ stats.currentMonthRevenue }}</div>
                        <p class="text-xs flex items-center gap-1">
                            <template v-if="stats.revenueChangePercentage !== 0">
                                <TrendingUp
                                    v-if="stats.revenueIncreased"
                                    class="h-3 w-3 text-green-500"
                                />
                                <TrendingDown
                                    v-else
                                    class="h-3 w-3 text-red-500"
                                />
                                <span
                                    :class="[
                                        stats.revenueIncreased ? 'text-green-500' : 'text-red-500',
                                    ]"
                                >
                                    {{ Math.abs(stats.revenueChangePercentage) }}%
                                </span>
                                <span class="text-muted-foreground">
                                    t.o.v. vorige maand
                                </span>
                            </template>
                            <span v-else class="text-muted-foreground">
                                Geen vergelijking beschikbaar
                            </span>
                        </p>
                    </CardContent>
                </Card>

                <!-- Pending Customers Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Wachtende Klanten
                        </CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pendingCustomers }}</div>
                        <p class="text-xs text-muted-foreground">
                            <a :href="admin.customers.pending()" class="text-primary hover:underline">
                                Bekijk wachtende klanten
                            </a>
                        </p>
                    </CardContent>
                </Card>

                <!-- Orders This Month Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Bestellingen Deze Maand
                        </CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.ordersThisMonth }}</div>
                        <p class="text-xs text-muted-foreground">
                            Alle bestellingen deze maand
                        </p>
                    </CardContent>
                </Card>

                <!-- Total Customers Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Totaal Aantal Klanten
                        </CardTitle>
                        <UsersRound class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalCustomers }}</div>
                        <p class="text-xs text-muted-foreground">
                            <a :href="'/admin/customers'" class="text-primary hover:underline">
                                Bekijk alle klanten
                            </a>
                        </p>
                    </CardContent>
                </Card>

                <!-- Active Products Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Producten Te Koop
                        </CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.activeProducts }}</div>
                        <p class="text-xs text-muted-foreground">
                            <a :href="'/admin/products'" class="text-primary hover:underline">
                                Bekijk producten
                            </a>
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Customer Dashboard -->
            <div v-if="!isAdmin && stats" class="space-y-6">
                <!-- Order Deadline Card -->
                <Card v-if="orderDeadline" :class="{ 'border-orange-500': orderDeadline.is_urgent }">
                    <CardContent class="pt-6">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-2">
                                <Clock class="h-4 w-4" :class="{ 'text-orange-500': orderDeadline.is_urgent }" />
                                <span class="text-sm font-medium">Besteldeadline:</span>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Bestel voor maandag 12:00 uur voor levering
                            </p>
                            <div class="flex gap-2 ml-auto">
                                <div class="text-center px-3 py-1.5 rounded-lg bg-muted">
                                    <div class="text-lg font-bold" :class="{ 'text-orange-500': orderDeadline.is_urgent }">
                                        {{ orderDeadline.days }}
                                    </div>
                                    <div class="text-[10px] text-muted-foreground">
                                        {{ orderDeadline.days === 1 ? 'dag' : 'dagen' }}
                                    </div>
                                </div>
                                <div class="text-center px-3 py-1.5 rounded-lg bg-muted">
                                    <div class="text-lg font-bold" :class="{ 'text-orange-500': orderDeadline.is_urgent }">
                                        {{ orderDeadline.hours }}
                                    </div>
                                    <div class="text-[10px] text-muted-foreground">
                                        {{ orderDeadline.hours === 1 ? 'uur' : 'uren' }}
                                    </div>
                                </div>
                                <div class="text-center px-3 py-1.5 rounded-lg bg-muted">
                                    <div class="text-lg font-bold" :class="{ 'text-orange-500': orderDeadline.is_urgent }">
                                        {{ orderDeadline.minutes }}
                                    </div>
                                    <div class="text-[10px] text-muted-foreground">
                                        min
                                    </div>
                                </div>
                            </div>
                            <div v-if="orderDeadline.is_urgent" class="text-xs text-orange-600 font-medium">
                                ⚠️ Minder dan 24 uur!
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Customer Statistics -->
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Open Orders Card -->
                    <Link href="/customer/orders" class="block">
                        <Card class="hover:shadow-lg transition-shadow cursor-pointer">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">
                                    Openstaande Bestellingen
                                </CardTitle>
                                <ShoppingCart class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">{{ (stats as CustomerStats).openOrders }}</div>
                                <p class="text-xs text-muted-foreground mt-1">
                                    Bestellingen in behandeling
                                </p>
                            </CardContent>
                        </Card>
                    </Link>

                    <!-- Quick Order Card -->
                    <Link href="/customer/favorites" class="block">
                        <Card class="hover:shadow-lg transition-shadow cursor-pointer">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">
                                    Quick Order Producten
                                </CardTitle>
                                <Zap class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">{{ (stats as CustomerStats).quickOrderProducts }}</div>
                                <p class="text-xs text-muted-foreground mt-1">
                                    Favoriete producten
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
