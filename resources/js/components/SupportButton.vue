<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { HelpCircle, Phone } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface Support {
    company_name: string | null;
    email: string | null;
    phone: string | null;
}

const page = usePage();

/**
 * Alleen klanten krijgen de hulpknop; voor beheerders is support null.
 */
const support = computed(() => (page.props.support as Support | null) ?? null);

const open = ref(false);
const form = useForm({ question: '' });

/**
 * Een tel:-link mag geen spaties of streepjes bevatten.
 */
const phoneLink = computed(() => support.value?.phone?.replace(/[^\d+]/g, '') ?? '');

const submit = () => {
    form.post('/customer/support', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            open.value = false;
        },
    });
};

const openDialog = () => {
    form.clearErrors();
    open.value = true;
};
</script>

<template>
    <template v-if="support">
        <Button
            type="button"
            size="icon-lg"
            class="fixed bottom-6 right-6 z-50 rounded-full shadow-lg"
            aria-label="Stel een vraag"
            title="Stel een vraag"
            @click="openDialog"
        >
            <HelpCircle class="h-6 w-6" />
        </Button>

        <Dialog :open="open" @update:open="open = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Stel een vraag</DialogTitle>
                    <DialogDescription>
                        We nemen zo snel mogelijk contact met u op.
                    </DialogDescription>
                </DialogHeader>

                <a
                    v-if="support.phone"
                    :href="`tel:${phoneLink}`"
                    class="flex items-center gap-3 rounded-lg border p-3 hover:bg-accent"
                >
                    <Phone class="h-5 w-5 shrink-0 text-muted-foreground" />
                    <span>
                        <span class="block text-xs text-muted-foreground">Liever direct bellen?</span>
                        <span class="font-medium">{{ support.phone }}</span>
                    </span>
                </a>

                <div class="grid gap-3 py-1">
                    <div class="rounded-lg bg-muted/50 p-3 text-sm">
                        <p class="font-medium">{{ support.company_name }}</p>
                        <p class="text-muted-foreground">{{ support.email }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="support_question">Uw vraag</Label>
                        <Textarea
                            id="support_question"
                            v-model="form.question"
                            rows="5"
                            placeholder="Waarmee kunnen we u helpen?"
                        />
                        <InputError :message="form.errors.question" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="open = false">Annuleren</Button>
                    <Button :disabled="form.processing" @click="submit">
                        {{ form.processing ? 'Versturen...' : 'Versturen' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </template>
</template>
