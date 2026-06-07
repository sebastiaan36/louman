<script setup lang="ts">
import { computed, ref } from 'vue';
import { formatPrice } from '@/lib/price';

interface MonthPoint {
    label: string;
    orders: number;
    revenue: number;
}

const props = defineProps<{
    data: MonthPoint[];
}>();

const maxRevenue = computed(() => Math.max(1, ...props.data.map((m) => m.revenue)));
const maxOrders = computed(() => Math.max(1, ...props.data.map((m) => m.orders)));

const hovered = ref<number | null>(null);

// Number of intervals on each axis (so TICKS + 1 labels, top to bottom).
const TICKS = 4;

const revenueTicks = computed(() =>
    Array.from({ length: TICKS + 1 }, (_, i) => (maxRevenue.value * (TICKS - i)) / TICKS),
);
const ordersTicks = computed(() =>
    Array.from({ length: TICKS + 1 }, (_, i) => Math.round((maxOrders.value * (TICKS - i)) / TICKS)),
);

// Each series is scaled to its own maximum (euros vs counts), so both axes
// are linear and the bars line up with their tick labels.
const barHeight = (value: number, max: number): string => `${(value / max) * 100}%`;
</script>

<template>
    <div>
        <!-- Legend -->
        <div class="mb-4 flex flex-wrap items-center gap-4 text-sm">
            <span class="flex items-center gap-2">
                <span class="h-3 w-3 rounded-sm bg-emerald-500"></span>
                Omzet (€) — linker as
            </span>
            <span class="flex items-center gap-2">
                <span class="h-3 w-3 rounded-sm bg-blue-500"></span>
                Bestellingen — rechter as
            </span>
        </div>

        <div class="flex">
            <!-- Left axis: revenue -->
            <div class="flex h-64 w-16 flex-col justify-between pr-2 text-right text-[10px] leading-none text-emerald-600">
                <span v-for="(tick, i) in revenueTicks" :key="`r-${i}`">€ {{ formatPrice(tick) }}</span>
            </div>

            <!-- Plot area -->
            <div class="flex h-64 flex-1 items-end gap-1 border-b border-l border-r">
                <div
                    v-for="(month, index) in data"
                    :key="month.label"
                    class="relative flex h-full flex-1 cursor-default items-end justify-center gap-1 rounded-t"
                    :class="hovered === index ? 'bg-muted' : ''"
                    @mouseenter="hovered = index"
                    @mouseleave="hovered = null"
                >
                    <!-- Tooltip -->
                    <div
                        v-if="hovered === index"
                        class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 w-max -translate-x-1/2 rounded-md border bg-popover px-3 py-2 text-xs text-popover-foreground shadow-md"
                    >
                        <div class="mb-1 font-semibold capitalize">{{ month.label }}</div>
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>
                            Omzet: € {{ formatPrice(month.revenue) }}
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-sm bg-blue-500"></span>
                            Bestellingen: {{ month.orders }}
                        </div>
                    </div>

                    <div
                        class="w-2.5 rounded-t bg-emerald-500 transition-all sm:w-3"
                        :class="month.revenue > 0 ? 'min-h-[2px]' : ''"
                        :style="{ height: barHeight(month.revenue, maxRevenue) }"
                    ></div>
                    <div
                        class="w-2.5 rounded-t bg-blue-500 transition-all sm:w-3"
                        :class="month.orders > 0 ? 'min-h-[2px]' : ''"
                        :style="{ height: barHeight(month.orders, maxOrders) }"
                    ></div>
                </div>
            </div>

            <!-- Right axis: orders -->
            <div class="flex h-64 w-8 flex-col justify-between pl-2 text-left text-[10px] leading-none text-blue-600">
                <span v-for="(tick, i) in ordersTicks" :key="`o-${i}`">{{ tick }}</span>
            </div>
        </div>

        <!-- Month labels, aligned under the plot area -->
        <div class="flex">
            <div class="w-16"></div>
            <div class="flex flex-1 gap-1">
                <span
                    v-for="month in data"
                    :key="`lbl-${month.label}`"
                    class="flex-1 text-center text-xs capitalize text-muted-foreground"
                >
                    {{ month.label }}
                </span>
            </div>
            <div class="w-8"></div>
        </div>
    </div>
</template>
