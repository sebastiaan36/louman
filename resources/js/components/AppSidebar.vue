<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FileText, FolderTree, LayoutGrid, Package, ShoppingCart, Store, Users, UsersRound, Zap } from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.role === 'admin');

// Admin menu sections
const adminSection1 = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
]);

const adminSection2 = computed<NavItem[]>(() => [
    {
        title: 'Wachtende klanten',
        href: admin.customers.pending(),
        icon: Users,
    },
    {
        title: 'Klanten',
        href: '/admin/customers',
        icon: UsersRound,
    },
]);

const adminSection3 = computed<NavItem[]>(() => [
    {
        title: 'Bestellingen',
        href: '/admin/orders',
        icon: ShoppingCart,
    },
    {
        title: 'Producten',
        href: '/admin/products',
        icon: Package,
    },
    {
        title: 'Categorieën',
        href: '/admin/categories',
        icon: FolderTree,
    },
]);

// Customer menu items
const customerNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Producten',
        href: '/customer/products',
        icon: Store,
    },
    {
        title: 'Quick Order',
        href: '/customer/favorites',
        icon: Zap,
    },
    {
        title: 'Mijn Bestellingen',
        href: '/customer/orders',
        icon: FileText,
    },
]);

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <template v-if="isAdmin">
                <NavMain :items="adminSection1" label="Platform" />
                <SidebarSeparator />
                <NavMain :items="adminSection2" label="Klanten" />
                <SidebarSeparator />
                <NavMain :items="adminSection3" label="Shop" />
            </template>
            <template v-else>
                <NavMain :items="customerNavItems" />
            </template>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
