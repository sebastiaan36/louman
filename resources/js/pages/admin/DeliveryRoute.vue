<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { GripVertical } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface Customer {
    id: number;
    company_name: string;
    street_name: string | null;
    house_number: string | null;
    city: string | null;
    route_order: number | null;
}

const props = defineProps<{
    customers: Customer[];
    selectedDay: string;
    days: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Rijroute' },
];

const DAY_LABELS: Record<string, string> = {
    maandag: 'Maandag',
    dinsdag: 'Dinsdag',
    woensdag: 'Woensdag',
    donderdag: 'Donderdag',
    vrijdag: 'Vrijdag',
    zaterdag: 'Zaterdag',
    zondag: 'Zondag',
    ophalen: 'Ophalen',
};

const list = ref<Customer[]>([...props.customers]);

watch(
    () => props.customers,
    (newCustomers) => {
        list.value = [...newCustomers];
    },
);

const onDayChange = (day: string) => {
    router.visit(`/admin/delivery-route?day=${day}`);
};

// Drag & drop state
const draggingIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);

const onDragStart = (index: number) => {
    draggingIndex.value = index;
};

const onDragOver = (e: DragEvent, index: number) => {
    e.preventDefault();
    dragOverIndex.value = index;
};

const onDragEnter = (index: number) => {
    if (draggingIndex.value === null || draggingIndex.value === index) return;
    const moved = list.value.splice(draggingIndex.value, 1)[0];
    list.value.splice(index, 0, moved);
    draggingIndex.value = index;
};

const onDrop = (e: DragEvent) => {
    e.preventDefault();
    dragOverIndex.value = null;
    draggingIndex.value = null;
    saveOrder();
};

const onDragEnd = () => {
    draggingIndex.value = null;
    dragOverIndex.value = null;
};

const saving = ref(false);

const saveOrder = () => {
    saving.value = true;
    router.post(
        '/admin/delivery-route/order',
        {
            day: props.selectedDay,
            order: list.value.map((c) => c.id),
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                saving.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Rijroute" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Rijroute</h1>
                    <p class="text-sm text-muted-foreground">
                        Sleep klanten om de bezorgroute per dag in te stellen
                    </p>
                </div>
                <div v-if="saving" class="text-sm text-muted-foreground">Opslaan...</div>
            </div>

            <div class="flex items-center gap-4">
                <label class="text-sm font-medium">Leverdag</label>
                <Select :model-value="selectedDay" @update:model-value="onDayChange">
                    <SelectTrigger class="w-48">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="day in days" :key="day" :value="day">
                            {{ DAY_LABELS[day] ?? day }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div v-if="list.length === 0" class="rounded-lg border border-dashed p-12 text-center">
                <p class="text-muted-foreground">
                    Geen klanten met leverdag
                    <strong>{{ DAY_LABELS[selectedDay] ?? selectedDay }}</strong>
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    Stel de leverdag in bij de klantinstellingen.
                </p>
            </div>

            <div v-else class="rounded-lg border">
                <div
                    v-for="(customer, index) in list"
                    :key="customer.id"
                    draggable="true"
                    class="flex cursor-grab items-center gap-4 border-b px-4 py-3 last:border-b-0 active:cursor-grabbing"
                    :class="{
                        'bg-muted opacity-50': draggingIndex === index,
                        'border-t-2 border-t-primary': dragOverIndex === index && draggingIndex !== index,
                    }"
                    @dragstart="onDragStart(index)"
                    @dragover="onDragOver($event, index)"
                    @dragenter="onDragEnter(index)"
                    @drop="onDrop"
                    @dragend="onDragEnd"
                >
                    <GripVertical class="h-4 w-4 shrink-0 text-muted-foreground" />
                    <span class="w-8 shrink-0 font-mono text-sm text-muted-foreground">{{ index + 1 }}</span>
                    <span class="font-medium">{{ customer.company_name }}</span>
                    <span v-if="customer.street_name" class="text-sm text-muted-foreground">
                        — {{ customer.street_name }} {{ customer.house_number }}, {{ customer.city }}
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
