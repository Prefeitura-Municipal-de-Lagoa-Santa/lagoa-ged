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
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h1 class="text-2xl md:text-3xl font-semibold text-foreground">
                    Criar Novo Usuário
                </h1>
            </div>

            <CardHeader>
                <CardTitle>Novo Usuário</CardTitle>
            </CardHeader>

            <UserForm 
                :form="form" 
                :user="null" :all-groups="props.allGroups" 
                :is-editing="false" 
                @submit="submitUserForm" 
            />
        </div>
    </DashboardLayout>
</template>