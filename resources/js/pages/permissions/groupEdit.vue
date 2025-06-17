<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { BreadcrumbItem } from '@/types';

// ✅ CORREÇÃO FINAL: A interface espera 'id' (string)
interface User {
    id: string;
    full_name: string;
    username: string;
    email: string;
}

// ✅ CORREÇÃO FINAL: A interface espera 'id' (string)
interface Group {
    id: string;
    name: string;
    description: string;
    members: User[];
};

interface Props {
    group: Group;
    allUsers: User[];
};

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Pagina Inicial', href: route('dashboard') },
    { title: 'Permissões' },
    { title: 'Grupos', href: route('groups.index') },
    { title: props.group.name }
]);

const form = useForm({
    name: props.group.name,
    description: props.group.description,
    // ✅ CORREÇÃO FINAL: Mapear usando 'member.id'
    user_ids: props.group.members.map(member => member.id),
});

const selectedAvailable = ref<string[]>([]);
const selectedMembers = ref<string[]>([]);

const availableUsers = computed(() => {
    // ✅ CORREÇÃO FINAL: Filtrar usando 'user.id'
    return props.allUsers.filter(user => !form.user_ids.includes(user.id));
});

const groupMembers = computed(() => {
    // ✅ CORREÇÃO FINAL: Filtrar usando 'user.id'
    return props.allUsers.filter(user => form.user_ids.includes(user.id));
});

function addMembers() {
    form.user_ids.push(...selectedAvailable.value);
    selectedAvailable.value = [];
}

function removeMembers() {
    form.user_ids = form.user_ids.filter(id => !selectedMembers.value.includes(id));
    selectedMembers.value = [];
}

function submit() {
    // ✅ CORREÇÃO FINAL: Usar 'props.group.id' na rota
    form.put(route('groups.update', { group: props.group.id }), {
        preserveScroll: true,
        onSuccess: () => { },
    });
}

</script>

<template>

    <Head :title="`Editar Grupo: ${props.group.name}`" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h1 class="text-2xl md:text-3xl font-semibold text-foreground">
                    Editar Grupo: {{ props.group.name }}
                </h1>
            </div>
            <form @submit.prevent="submit">
                <Card>

                    <CardContent class="grid gap-6">
                        <div class="grid gap-2">
                            <Label for="name">Nome</Label>
                            <Input id="name" type="text" v-model="form.name" />
                            <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="description">Descrição</Label>
                            <Textarea id="description" v-model="form.description" />
                            <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description
                                }}</div>
                        </div>

                        <div class="grid gap-2">
                            <Label>Membros do Grupo</Label>
                            <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] items-center gap-4">
                                <div class="flex flex-col gap-2">
                                    <span class="text-sm font-medium text-center">Usuários Disponíveis</span>
                                    <select multiple class="bg-card border rounded-md h-48 p-2"
                                        v-model="selectedAvailable">
                                        <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                            {{ user.full_name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <Button type="button" @click="addMembers"
                                        :disabled="selectedAvailable.length === 0">&gt;&gt;</Button>
                                    <Button type="button" @click="removeMembers"
                                        :disabled="selectedMembers.length === 0" variant="destructive">&lt;&lt;</Button>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <span class="text-sm font-medium text-center">Membros no Grupo</span>
                                    <select multiple class="bg-card border rounded-md h-48 p-2"
                                        v-model="selectedMembers">
                                        <option v-for="user in groupMembers" :key="user.id" :value="user.id">
                                            {{ user.full_name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="form.errors.user_ids" class="text-sm text-red-500 mt-2">{{
                                form.errors.user_ids }}</div>
                        </div>
                    </CardContent>

                    <CardFooter class="flex justify-end">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </div>
    </DashboardLayout>
</template>