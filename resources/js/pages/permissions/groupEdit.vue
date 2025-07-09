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
    form.put(route('groups.update', { group: props.group.id }), {
        preserveScroll: true,
        onSuccess: () => {}, // Ou adicione uma notificação de sucesso
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

            <CardHeader>
                <CardTitle>Detalhes do Grupo</CardTitle>
            </CardHeader>

            <GroupForm 
                :form="form" 
                :all-users="props.allUsers" 
                :is-editing="true" 
                :group="props.group" @submit="submitGroupForm" 
            />
        </div>
    </DashboardLayout>
</template>