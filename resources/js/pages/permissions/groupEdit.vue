<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { BreadcrumbItem } from '@/types';
import { CardHeader, CardTitle } from '@/components/ui/card'; // Para o título do Card
import GroupForm from '@/components/forms/GroupForm.vue'; // Importa o novo componente de formulário

// Interfaces (mantenha aqui para as props recebidas pela página Inertia)
interface User {
    id: string;
    full_name: string;
    username: string;
    email: string;
}

interface Group {
    id: string;
    name: string;
    description: string;
    members: User[];
    is_protected?: boolean; // Adicione se existir no seu modelo Group
}

interface Props {
    group: Group;
    allUsers: User[];
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Página Inicial', href: route('dashboard') },
    { title: 'Permissões' },
    { title: 'Grupos', href: route('groups.index') },
    { title: props.group.name }
]);

// O objeto 'form' é definido aqui e passado para o GroupForm
const form = useForm({
    name: props.group.name,
    description: props.group.description,
    user_ids: props.group.members.map(member => member.id),
});

// A função de submissão para atualizar o grupo
function submitGroupForm() {
    form.put(route('groups.update', props.group.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // A notificação será criada automaticamente pelo middleware
            console.log('Grupo atualizado com sucesso');
        },
        onError: (errors) => {
            console.error('Erro ao atualizar grupo:', errors);
        }
    });
}
</script>

<template>
    <Head :title="`Editar Grupo: ${props.group.name}`" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8 px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-stone-50 mb-2">
                    Editar Grupo: {{ props.group.name }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-lg">
                    Gerencie os detalhes e membros do grupo abaixo.
                </p>
            </div>

            <div class="bg-white dark:bg-stone-950/95 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-stone-800 max-w-2xl mx-auto">
                <div class="mb-6 border-b border-gray-200 dark:border-stone-800 pb-4">
                    <h2 class="text-2xl font-semibold text-gray-800 dark:text-stone-50 flex items-center gap-2">
                        <span class="inline-block w-2 h-8 bg-blue-600 rounded-full mr-2"></span>
                        Detalhes do Grupo
                    </h2>
                </div>
                <GroupForm 
                    :form="form" 
                    :all-users="props.allUsers" 
                    :is-editing="true" 
                    :group="props.group" @submit="submitGroupForm" 
                />
            </div>
        </div>
    </DashboardLayout>
</template>