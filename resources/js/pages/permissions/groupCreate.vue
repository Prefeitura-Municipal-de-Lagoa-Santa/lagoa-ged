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
    is_protected?: boolean;
}

interface Props {
    allUsers: User[]; // Em criação, só precisamos de todos os usuários
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Página Inicial', href: route('dashboard') },
    { title: 'Permissões' },
    { title: 'Grupos', href: route('groups.index') },
    { title: 'Novo' }
]);

// O objeto 'form' é definido aqui com valores padrão para criação
const form = useForm({
    name: '',
    description: '',
    user_ids: [], // Começa sem membros selecionados
});

// A função de submissão para criar um novo grupo
function submitGroupForm() {
    form.post(route('groups.store'), { // Rota para criação de grupo
        preserveScroll: true,
        onSuccess: () => {
            // Você pode redirecionar para a página de edição do grupo recém-criado
            // ou para a lista de grupos.
            // Exemplo: Inertia.visit(route('groups.edit', data.id)); // Se a resposta contiver o ID
            // Ou apenas: Inertia.visit(route('groups.index'));
        },
    });
}
</script>

<template>
    <Head title="Criar Novo Grupo" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h1 class="text-2xl md:text-3xl font-semibold text-foreground">
                    Criar Novo Grupo
                </h1>
            </div>

            <CardHeader>
                <CardTitle>Novo Grupo</CardTitle>
            </CardHeader>

            <GroupForm 
                :form="form" 
                :all-users="props.allUsers" 
                :is-editing="false" 
                @submit="submitGroupForm" 
            />
        </div>
    </DashboardLayout>
</template>