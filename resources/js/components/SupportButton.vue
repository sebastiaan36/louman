<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { onClickOutside, onKeyStroke } from '@vueuse/core';
import { HelpCircle, Phone, X } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
const balloon = ref<HTMLElement | null>(null);
const questionField = ref<InstanceType<typeof Textarea> | null>(null);
const form = useForm({ question: '' });

/**
 * Een tel:-link mag geen spaties of streepjes bevatten.
 */
const phoneLink = computed(() => support.value?.phone?.replace(/[^\d+]/g, '') ?? '');

const toggle = async () => {
    open.value = !open.value;

    if (! open.value) {
        return;
    }

    form.clearErrors();
    await nextTick();
    (questionField.value?.$el as HTMLTextAreaElement | undefined)?.focus();
};

// Klikken naast de ballon of Escape sluit hem, zoals bij elk ander
// zwevend paneel in het portaal.
onClickOutside(balloon, () => {
    open.value = false;
});

onKeyStroke('Escape', () => {
    open.value = false;
});

const submit = () => {
    form.post('/customer/support', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            open.value = false;
        },
    });
};
</script>

<template>
    <div v-if="support" ref="balloon" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">
        <!-- De ballon zelf: hangt boven de knop, met een puntje dat ernaar wijst. -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-if="open"
                role="dialog"
                aria-label="Stel uw vraag"
                class="relative w-[min(22rem,calc(100vw-3rem))] rounded-xl border bg-background p-4 shadow-xl"
            >
                <!-- Het puntje van de ballon, uitgelijnd op het midden van de knop. -->
                <div
                    class="absolute -bottom-1.5 right-4 h-3 w-3 rotate-45 border-b border-r bg-background"
                    aria-hidden="true"
                ></div>

                <div class="space-y-3">
                    <div>
                        <h2 class="font-semibold">Stel uw vraag</h2>
                        <p class="text-xs text-muted-foreground">
                            We nemen zo snel mogelijk contact met u op.
                        </p>
                    </div>

                    <a
                        v-if="support.phone"
                        :href="`tel:${phoneLink}`"
                        class="flex items-center gap-3 rounded-lg border p-2.5 hover:bg-accent"
                    >
                        <Phone class="h-4 w-4 shrink-0 text-muted-foreground" />
                        <span>
                            <span class="block text-xs text-muted-foreground">Liever direct bellen?</span>
                            <span class="text-sm font-medium">{{ support.phone }}</span>
                        </span>
                    </a>

                    <div class="rounded-lg bg-muted/50 p-2.5 text-sm">
                        <p class="font-medium">{{ support.company_name }}</p>
                        <p class="text-xs text-muted-foreground">{{ support.email }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="support_question" class="text-sm">Uw vraag</Label>
                        <Textarea
                            id="support_question"
                            ref="questionField"
                            v-model="form.question"
                            rows="4"
                            placeholder="Waarmee kunnen we u helpen?"
                        />
                        <InputError :message="form.errors.question" />
                    </div>

                    <Button class="w-full" :disabled="form.processing" @click="submit">
                        {{ form.processing ? 'Versturen...' : 'Versturen' }}
                    </Button>
                </div>
            </div>
        </Transition>

        <Button
            type="button"
            size="lg"
            class="rounded-full px-5 shadow-lg"
            :aria-expanded="open"
            @click="toggle"
        >
            <X v-if="open" class="h-5 w-5" />
            <HelpCircle v-else class="h-5 w-5" />
            {{ open ? 'Sluiten' : 'Stel uw vraag' }}
        </Button>
    </div>
</template>
