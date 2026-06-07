<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ShoppingCart, Users, Euro, TrendingUp, TrendingDown, FileText, UsersRound, Package, BarChart3, CalendarRange } from 'lucide-vue-next';
import StatsBarChart from '@/components/StatsBarChart.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatPrice } from '@/lib/price';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';

interface AdminStats {
    pendingOrders: number;
    currentMonthRevenue: string;
    currentYearRevenue: string;
    revenueChangePercentage: number;
    revenueIncreased: boolean;
    pendingCustomers: number;
    ordersThisMonth: number;
    ordersThisYear: number;
    totalCustomers: number;
    activeProducts: number;
}

interface ChartPoint {
    label: string;
    orders: number;
    revenue: number;
}

defineProps<{
    stats: AdminStats;
    chart: ChartPoint[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Cijfers',
    },
];
</script>

<template>
    <Head title="Cijfers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-2xl font-bold">Cijfers</h1>
                <p class="text-sm text-muted-foreground">
                    Volledig overzicht van de cijfers, inclusief omzet
                </p>
            </div>

            <!-- Chart: orders & revenue per month -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <BarChart3 class="h-5 w-5" />
                        Omzet en bestellingen per maand
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <StatsBarChart :data="chart" />
                </CardContent>
            </Card>

            <!-- Yearly figures -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card class="border-l-4 border-l-emerald-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Omzet Dit Jaar
                        </CardTitle>
                        <CalendarRange class="h-4 w-4 text-emerald-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-600">€{{ formatPrice(stats.currentYearRevenue) }}</div>
                        <p class="text-xs text-muted-foreground">Totale omzet in {{ new Date().getFullYear() }}</p>
                    </CardContent>
                </Card>

                <Card class="border-l-4 border-l-blue-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            Bestellingen Dit Jaar
                        </CardTitle>
                        <CalendarRange class="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">{{ stats.ordersThisYear }}</div>
                        <p class="text-xs text-muted-foreground">Alle bestellingen in {{ new Date().getFullYear() }}</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
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
                        <div class="text-2xl font-bold">€{{ formatPrice(stats.currentMonthRevenue) }}</div>
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
        </div>
    </AppLayout>
</template>
