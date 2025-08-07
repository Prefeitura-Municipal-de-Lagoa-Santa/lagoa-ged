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
        <div class="container mx-auto py-8 px-4">
            <div class="mb-8">
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-stone-50 mb-2">
                    Criar Novo Grupo
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-lg">
                    Preencha os dados abaixo para criar um novo grupo e adicionar membros.
                </p>
            </div>

            <div class="bg-white dark:bg-stone-950/95 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-stone-800 max-w-2xl mx-auto">
                <div class="mb-6 border-b border-gray-200 dark:border-stone-800 pb-4">
                    <h2 class="text-2xl font-semibold text-gray-800 dark:text-stone-50 flex items-center gap-2">
                        <span class="inline-block w-2 h-8 bg-blue-600 rounded-full mr-2"></span>
                        Novo Grupo
                    </h2>
                </div>
                <GroupForm 
                    :form="form" 
                    :all-users="props.allUsers" 
                    :is-editing="false" 
                    @submit="submitGroupForm" 
                />
            </div>
        </div>
    </DashboardLayout>
</template>