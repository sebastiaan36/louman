<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const isCustomer = computed(() => user.value?.role === 'customer');

const sidebarNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Profiel',
            href: editProfile(),
        },
    ];

    // Add Delivery Addresses for customers only
    if (isCustomer.value) {
        items.push({
            title: 'Afleveradressen',
            href: '/customer/delivery-addresses',
        });
    }

    items.push(
        {
            title: 'Wachtwoord',
            href: editPassword(),
        },
        {
            title: 'Uiterlijk',
            href: editAppearance(),
        }
    );

    return items;
});

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Instellingen"
            description="Beheer je profiel en accountinstellingen"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="Instellingen"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component v-if="item.icon" :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
