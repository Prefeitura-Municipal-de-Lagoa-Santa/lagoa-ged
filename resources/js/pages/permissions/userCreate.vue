<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { BreadcrumbItem } from '@/types';
import UserForm from '@/components/forms/UserForm.vue'; // Importa o novo componente de formulário
import { CardHeader, CardTitle } from '@/components/ui/card'; // Importa os componentes de card para o título
import Password from '../settings/Password.vue';


// Interfaces (copiadas para cá para manter as tipagens, pois as props são recebidas aqui)
interface Group {
    id: string;
    name: string;
}

interface Props {
    allGroups: Group[]; // Em criação, só precisamos de todos os grupos
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Página Inicial', href: route('dashboard') },
    { title: 'Permissões' },
    { title: 'Usuários', href: route('users.index') },
    { title: 'Criar Novo Usuário' }
]);

// O objeto 'form' é definido aqui com valores padrão para criação
const form = useForm({
    id: null, // Não há ID para um novo usuário
    full_name: '',
    email: '',
    username: '',
    password:'',
    password_confirmation: '',
    userGroups: [], // Começa sem grupos selecionados
});

// A função de submissão para criar um novo usuário
function submitUserForm() {
    form.transform(data => ({
        ...data,
        userGroups: data.userGroups.map(group => group.id), // Envia apenas os IDs dos grupos
    })).post(route('users.store'), { // Rota para criação de usuário
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Criar Novo Usuário" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8 px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-stone-50 mb-2">
                    Cadastrar Novo Usuário
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-lg">
                    Preencha os dados abaixo para cadastrar um novo usuário e atribuir grupos de permissão.
                </p>
            </div>

            <div class="bg-white dark:bg-stone-950/95 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-stone-800 max-w-2xl mx-auto">
                <div class="mb-6 border-b border-gray-200 dark:border-stone-800 pb-4">
                    <h2 class="text-2xl font-semibold text-gray-800 dark:text-stone-50 flex items-center gap-2">
                        <span class="inline-block w-2 h-8 bg-blue-600 rounded-full mr-2"></span>
                        Novo Usuário
                    </h2>
                </div>
                <UserForm 
                    :form="form" 
                    :user="null" :all-groups="props.allGroups" 
                    :is-editing="false" 
                    @submit="submitUserForm" 
                />
            </div>
        </div>
    </DashboardLayout>
</template>