<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);

const visible = ref(false);
const currentFlash = ref<{ type: 'success' | 'error'; message: string } | null>(null);

watch(flash, (newFlash) => {
    if (newFlash?.success) {
        currentFlash.value = { type: 'success', message: newFlash.success };
        visible.value = true;
        setTimeout(() => { visible.value = false; }, 5000);
    } else if (newFlash?.error) {
        currentFlash.value = { type: 'error', message: newFlash.error };
        visible.value = true;
        setTimeout(() => { visible.value = false; }, 8000);
    }
}, { immediate: true });
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />

            <div v-if="visible && currentFlash" class="px-6 pt-4">
                <div
                    :class="[
                        'flex items-start justify-between rounded-lg border px-4 py-3 text-sm',
                        currentFlash.type === 'success'
                            ? 'border-green-200 bg-green-50 text-green-800'
                            : 'border-red-200 bg-red-50 text-red-800'
                    ]"
                >
                    <span>{{ currentFlash.message }}</span>
                    <button @click="visible = false" class="ml-4 opacity-60 hover:opacity-100">&times;</button>
                </div>
            </div>

            <slot />
        </AppContent>
    </AppShell>
</template>
