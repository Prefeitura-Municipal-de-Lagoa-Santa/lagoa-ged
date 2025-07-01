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
import GroupManagerModal from '@/components/modals/GroupManagerModal.vue';


// ✅ CORREÇÃO FINAL: A interface espera 'id' (string)
interface User {
    id: string;
    full_name: string;
    username: string;
    email: string;
    is_protected: boolean;
    is_ldap: boolean;
}

// ✅ CORREÇÃO FINAL: A interface espera 'id' (string)
interface Group {
    id: string;
    name: string;
};

interface Props {
    userGroups: Group[];
    user: User;
    allGroups: Group[];
};

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Pagina Inicial', href: route('dashboard') },
    { title: 'Permissões' },
    { title: 'Usuários', href: route('users.index') },
    { title: props.user.username }
]);

const form = useForm({
    id: props.user.id,
    full_name: props.user.full_name,
    email: props.user.email,
    username: props.user.username,
    userGroups: props.userGroups,
});

const isModalOpen = ref(false);

function handleGroupUpdate(updatedGroups: Group[]) {
    form.userGroups = updatedGroups;
}
function submit() {
    form.transform(data => ({
        ...data,
        userGroups: data.userGroups.map(group => group.id),
    })).put(route('users.update', { user: props.user.id }), {
        preserveScroll: true,
    });
}


</script>

<template>

    <Head :title="`Editar Usuário: ${props.user.full_name}`" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h1 class="text-2xl md:text-3xl font-semibold text-foreground">
                    Editar Usuário: {{ props.user.full_name }}
                </h1>
            </div>
            <form @submit.prevent="submit">
                <Card>

                    <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="gap-2 md:col-span-2">
                            <Label class="h-8" for="name">Nome</Label>
                            <Input :disabled="props.user.is_ldap || props.user.is_protected" id="name" type="text" v-model="form.full_name" />
                            <div v-if="form.errors.full_name" class="text-sm text-red-500">{{ form.errors.full_name }}
                            </div>
                        </div>
                        <div class="grid gap-3">
                            <Label for="username">Usuário</Label>
                            <Input :disabled="props.user.is_ldap || props.user.is_protected" id="username" type="text" v-model="form.username" />
                            <div v-if="form.errors.username" class="text-sm text-red-500">{{ form.errors.username }}
                            </div>
                        </div>
                        <div class="grid gap-3">
                            <Label for="description">E-mail</Label>
                            <Input :disabled="props.user.is_ldap || props.user.is_protected" id="description" v-model="form.email" />
                            <div v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</div>
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <div class="flex justify-between items-center mb-2">
                                <Label>Grupos</Label>
                                <Button type="button" variant="outline" size="sm" @click="isModalOpen = true">
                                    Editar Grupos
                                </Button>
                            </div>

                            <div v-if="form.userGroups && form.userGroups.length > 0"
                                class="flex flex-wrap gap-2 rounded-lg border bg-muted/50 p-3 min-h-[40px] items-center">

                                <span v-for="group in form.userGroups" :key="group.id"
                                    class="inline-flex items-center rounded-full bg-muted px-10 py-1 text-sm font-semibold shadow-sm">
                                    {{ group.name }}
                                </span>
                            </div>

                            <div v-else
                                class="flex items-center justify-center rounded-lg border p-3 min-h-[40px] text-sm text-muted-foreground">
                                <span>Este usuário não pertence a nenhum grupo.</span>
                            </div>
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
        <GroupManagerModal 
            v-model="isModalOpen"
            :all-groups="props.allGroups"
            :initial-selected-groups="form.userGroups"
            :username="props.user.username"
            @confirm="handleGroupUpdate"
        />
    </DashboardLayout>
</template>
