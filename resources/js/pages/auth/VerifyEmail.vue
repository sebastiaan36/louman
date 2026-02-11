<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { ref } from 'vue';

defineProps<{
    status?: string;
}>();

const processing = ref(false);

const resendVerification = () => {
    processing.value = true;
    router.post('/email/verification-notification', {}, {
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <AuthLayout
        title="Verify email"
        description="Please verify your email address by clicking on the link we just emailed to you."
    >
        <Head title="Email verification" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            A new verification link has been sent to the email address you
            provided during registration.
        </div>

        <div class="space-y-6 text-center">
            <Button
                @click="resendVerification"
                :disabled="processing"
                variant="secondary"
            >
                <Spinner v-if="processing" />
                Resend verification email
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm"
            >
                Log out
            </TextLink>
        </div>
    </AuthLayout>
</template>
