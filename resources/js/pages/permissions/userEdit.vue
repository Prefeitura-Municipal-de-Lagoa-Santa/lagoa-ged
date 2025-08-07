<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { BreadcrumbItem } from '@/types';
import UserForm from '@/components/forms/UserForm.vue'; // Importa o novo componente de formulário
import { CardHeader, CardTitle } from '@/components/ui/card'; // Importa os componentes de card para o título

// Interfaces (copiadas para cá para manter as tipagens, pois as props são recebidas aqui)
interface User {
    id: string;
    full_name: string;
    username: string;
    email: string;
    is_protected: boolean;
    is_ldap: boolean;
}

interface Group {
    id: string;
    name: string;
}

interface Props {
    userGroups: Group[];
    user: User;
    allGroups: Group[];
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Página Inicial', href: route('dashboard') },
    { title: 'Permissões' },
    { title: 'Usuários', href: route('users.index') },
    { title: props.user.username }
]);

// O objeto 'form' é definido aqui e passado para o UserForm
const form = useForm({
    id: props.user.id,
    full_name: props.user.full_name,
    email: props.user.email,
    username: props.user.username,
    userGroups: props.userGroups || [], // Ensure userGroups is an array of Group objects
});

// A função de submissão é definida aqui e passada para o UserForm
function submitUserForm() {
    const userGroups = Array.isArray(form.userGroups) ? form.userGroups : [];
    const userGroupIds = userGroups.map((group: any) => group.id || group);
    
    form.transform((data) => ({
        ...data,
        userGroups: userGroupIds
    })).put(route('users.update', { user: props.user.id }), {
        preserveScroll: true,
        onSuccess: () => {
            // A notificação será criada automaticamente pelo middleware
            console.log('Usuário atualizado com sucesso');
        },
        onError: (errors) => {
            console.error('Erro ao atualizar usuário:', errors);
        }
    });
}
</script>

<template>
    <Head :title="`Editar Usuário: ${props.user.full_name}`" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8 px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-stone-50 mb-2">
                    Editar Usuário: {{ props.user.full_name }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-lg">
                    Gerencie os dados e grupos de permissão do usuário abaixo.
                </p>
            </div>

            <div class="bg-white dark:bg-stone-950/95 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-stone-800 max-w-2xl mx-auto">
                <div class="mb-6 border-b border-gray-200 dark:border-stone-800 pb-4">
                    <h2 class="text-2xl font-semibold text-gray-800 dark:text-stone-50 flex items-center gap-2">
                        <span class="inline-block w-2 h-8 bg-blue-600 rounded-full mr-2"></span>
                        Detalhes do Usuário
                    </h2>
                </div>
                <UserForm 
                    :form="form" 
                    :user="props.user" 
                    :all-groups="props.allGroups" 
                    :is-editing="true" 
                    @submit="submitUserForm" 
                />
            </div>
        </div>
    </DashboardLayout>
</template>