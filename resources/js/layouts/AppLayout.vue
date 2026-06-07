<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Give the admin area a green accent (primary buttons etc.) so the main
// actions stand out. Applied on the document element so it also covers
// dialogs and popovers that teleport outside the layout.
const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

watchEffect(() => {
    if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('admin-theme', isAdmin.value);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>
