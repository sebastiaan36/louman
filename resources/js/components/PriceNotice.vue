<script setup lang="ts">
import { Info } from 'lucide-vue-next';
import { Alert, AlertDescription } from '@/components/ui/alert';
</script>

<template>
    <Alert>
        <Info class="size-4" />
        <AlertDescription class="text-muted-foreground">
            De getoonde prijzen in het klantportaal zijn onze standaardprijzen. Uw persoonlijk
            afgesproken prijzen worden bij de facturatie automatisch toegepast en zijn terug te
            zien op uw factuur.
        </AlertDescription>
    </Alert>
</template>
