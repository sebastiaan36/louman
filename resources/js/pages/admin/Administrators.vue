<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Plus, ShieldCheck, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface Administrator {
    id: number;
    name: string;
    email: string;
    created_at: string;
    is_self: boolean;
}

defineProps<{
    administrators: Administrator[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Beheerders',
    },
];

const createOpen = ref(false);

const deleteTarget = ref<Administrator | null>(null);
const deleting = ref(false);

const confirmDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(`/admin/administrators/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
};
</script>

<template>
    <Head title="Beheerders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <ShieldCheck class="h-6 w-6" />
                        Beheerders
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Beheer de accounts met toegang tot de adminomgeving
                    </p>
                </div>
                <Button @click="createOpen = true">
                    <Plus class="h-4 w-4 mr-2" />
                    Beheerder toevoegen
                </Button>
            </div>

            <div class="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Naam</TableHead>
                            <TableHead>E-mailadres</TableHead>
                            <TableHead>Aangemaakt op</TableHead>
                            <TableHead></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="admin in administrators" :key="admin.id">
                            <TableCell class="font-medium">
                                {{ admin.name }}
                                <Badge v-if="admin.is_self" variant="secondary" class="ml-2">Jij</Badge>
                            </TableCell>
                            <TableCell>{{ admin.email }}</TableCell>
                            <TableCell>{{ admin.created_at }}</TableCell>
                            <TableCell class="text-right">
                                <Button
                                    v-if="!admin.is_self"
                                    size="sm"
                                    variant="destructive"
                                    @click="deleteTarget = admin"
                                >
                                    <Trash2 class="h-4 w-4 mr-2" />
                                    Verwijderen
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Create Administrator Dialog -->
        <Dialog :open="createOpen" @update:open="createOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Beheerder toevoegen</DialogTitle>
                    <DialogDescription>
                        Maak een nieuw beheerdersaccount aan. Deze persoon kan direct inloggen
                        met het opgegeven e-mailadres en wachtwoord.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    action="/admin/administrators"
                    method="post"
                    :reset-on-success="['name', 'email', 'password']"
                    @success="createOpen = false"
                    v-slot="{ errors, processing }"
                    class="space-y-4"
                >
                    <div class="grid gap-2">
                        <Label for="name">Naam *</Label>
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            name="name"
                            placeholder="Volledige naam"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">E-mailadres *</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            name="email"
                            placeholder="naam@louman.nl"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Wachtwoord *</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            autocomplete="new-password"
                            name="password"
                            placeholder="Wachtwoord"
                        />
                        <p class="text-xs text-muted-foreground">
                            Minimaal 12 tekens, met hoofd- en kleine letters, een cijfer en een symbool.
                        </p>
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Bevestig wachtwoord *</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="Bevestig wachtwoord"
                        />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="createOpen = false">
                            Annuleren
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{ processing ? 'Aanmaken...' : 'Beheerder aanmaken' }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="deleteTarget !== null" @update:open="deleteTarget = null">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Beheerder verwijderen</DialogTitle>
                    <DialogDescription>
                        Weet je zeker dat je <strong>{{ deleteTarget?.name }}</strong> wilt verwijderen?
                        Deze persoon verliest direct toegang tot de adminomgeving. Deze actie kan
                        niet ongedaan worden gemaakt.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteTarget = null">Annuleren</Button>
                    <Button variant="destructive" :disabled="deleting" @click="confirmDelete">
                        Definitief verwijderen
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
