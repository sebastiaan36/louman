<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';
import { Input } from '@/components/ui/input';

/**
 * A password field with a button to reveal what was typed. Shared so the login,
 * registration and invitation screens stay identical instead of each keeping
 * their own copy of the markup.
 *
 * Any other attribute — name, autocomplete, placeholder, required, tabindex —
 * falls through to the input.
 */
defineOptions({ inheritAttrs: false });

defineProps<{
    id: string;
}>();

const showPassword = ref(false);
</script>

<template>
    <div class="relative">
        <Input
            :id="id"
            :type="showPassword ? 'text' : 'password'"
            class="pr-10"
            v-bind="$attrs"
        />
        <button
            type="button"
            :tabindex="-1"
            :aria-label="showPassword ? 'Wachtwoord verbergen' : 'Wachtwoord tonen'"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground"
            @click="showPassword = !showPassword"
        >
            <EyeOff v-if="showPassword" class="h-4 w-4" />
            <Eye v-else class="h-4 w-4" />
        </button>
    </div>
</template>
