<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    links: PaginationLink[];
    currentPage: number;
    from: number;
    to: number;
    total: number;
    lastPage: number;
}

defineProps<Props>();

// Helper to parse label text (remove HTML entities)
const parseLabel = (label: string): string => {
    return label
        .replace('&laquo;', '«')
        .replace('&raquo;', '»')
        .replace('Previous', 'Vorige')
        .replace('Next', 'Volgende');
};
</script>

<template>
    <div v-if="links && lastPage > 1" class="flex items-center justify-between gap-4 border-t px-4 py-3">
        <!-- Info text -->
        <div class="text-sm text-muted-foreground">
            Resultaten {{ from }} tot {{ to }} van {{ total }}
        </div>

        <!-- Pagination links -->
        <div class="flex items-center gap-1">
            <template v-for="(link, index) in links" :key="index">
                <!-- Previous button -->
                <Link
                    v-if="index === 0"
                    :href="link.url || '#'"
                    :class="{ 'pointer-events-none opacity-50': !link.url }"
                    preserve-state
                >
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!link.url"
                    >
                        <ChevronLeft class="h-4 w-4 mr-1" />
                        {{ parseLabel(link.label) }}
                    </Button>
                </Link>

                <!-- Next button -->
                <Link
                    v-else-if="index === links.length - 1"
                    :href="link.url || '#'"
                    :class="{ 'pointer-events-none opacity-50': !link.url }"
                    preserve-state
                >
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!link.url"
                    >
                        {{ parseLabel(link.label) }}
                        <ChevronRight class="h-4 w-4 ml-1" />
                    </Button>
                </Link>

                <!-- Page number buttons -->
                <Link
                    v-else
                    :href="link.url || '#'"
                    :class="{ 'pointer-events-none': !link.url }"
                    preserve-state
                >
                    <Button
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        class="min-w-[2.5rem]"
                    >
                        {{ link.label }}
                    </Button>
                </Link>
            </template>
        </div>
    </div>
</template>
