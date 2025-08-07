<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue'; // Ajuste o caminho se necessário
import { Head, Link, router } from '@inertiajs/vue3'; // Adicionado Link para possíveis botões
import { ref, watch } from 'vue'; // Importar ref para dados reativos
import { Button } from '@/components/ui/button'; // Supondo que você tenha este componente
import { SquarePen, UserPlus2, } from 'lucide-vue-next';
import { BreadcrumbItem } from '@/types';
import Pagination from '@/components/ui/Pagination.vue';

interface User {
  id: number;
  full_name: string;
  username: string;
  email?: string;
  members: [];
};

// Interface para o objeto de paginação que o Laravel envia
interface PaginatedUsers {
  data: User[];
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
};

interface Props {
  users: PaginatedUsers;
  filters?: Record<string, string | number>; // Um objeto para os filtros (opcional por enquanto)
};

const props = defineProps<Props>();

const form = ref({
  per_page: props.filters?.per_page || 25,
});

const applyFilters = () => {
  const cleanForm = Object.fromEntries(
    Object.entries(form.value).filter(([, value]) => value !== '' && value !== null)
  );

  router.get(route('users.index'), cleanForm, {
    preserveState: true,
    preserveScroll: true,
  });
};

const handlePerPageChange = (perPage: number) => {
  // Atualiza diretamente
  const newFilters = {
    ...form.value,
    per_page: perPage
  };
  
  // Remove valores vazios
  const cleanForm = Object.fromEntries(
    Object.entries(newFilters).filter(([, value]) => value !== '' && value !== null && value !== 0)
  );
  
  router.get(route('users.index'), cleanForm, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      form.value.per_page = perPage;
    }
  });
};

const breadcrumbs:BreadcrumbItem[] = [
    { title: 'Página Inicial', href: route('dashboard') }, 
    { title: 'Permissões', href: route('users.index') }, 
    { title: 'Usuários', href: route('users.index') } 
];


</script>

<template>
    <Head title="Documentos" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h1 class="text-2xl md:text-3xl font-semibold text-foreground">
                    Usuários
                </h1>
                <Button as-child>
                    <Link :href="route('users.create')"> <UserPlus2 class="mr-2 h-4 w-4"/>
                        Criar Usuário Local
                    </Link>
                </Button>
            </div>

            <div class="overflow-x-auto bg-gray-900 rounded-lg shadow-md hidden md:block">
                <table class="min-w-full text-white">
                                        <thead class="bg-stone-800 dark:bg-stone-800/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white-300 dark:text-gray-200 uppercase tracking-wider">Usuário</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white-300 dark:text-gray-200 uppercase tracking-wider">Nome Completo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white-300 dark:text-gray-200 uppercase tracking-wider">E-mail</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white-300 dark:text-gray-200 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-50 dark:bg-stone-950/95 divide-y divide-gray-700 dark:divide-stone-700">
                        <tr v-if="props.users.data.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-800">
                                Nenhum usuário encontrado.
                            </td>
                        </tr>

                        <tr v-for="u in props.users.data" :key="u.id" class="hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-700 dark:text-white">{{ u.username }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-700 dark:text-white">{{ u.full_name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400">{{ u.email || u.username }}</td>
                            <td class="px-6 py-4 text-sm font-medium flex items-center justify-center space-x-3">
                                <a :href="route('users.edit', u.id)" class="text-green-600 dark:text-green-400 hover:text-green-300" title="Editar">
                                    <SquarePen class="h-5 w-5"/>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Versão Mobile -->
            <div class="md:hidden">
                <div v-if="props.users.data.length === 0"
                    class="bg-card p-6 rounded-lg shadow-md text-center text-sm text-muted-foreground">
                    Nenhum usuário encontrado.
                </div>
                <div v-else class="grid gap-4">
                    <div v-for="u in props.users.data" :key="u.id"
                        class="bg-card p-4 rounded-lg shadow-md border border-border">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="text-lg font-semibold text-foreground break-words pr-2">
                                {{ u.full_name }}
                            </h3>
                            <a :href="route('users.edit', u.id)" 
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 p-2 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/20" 
                                title="Editar">
                                <SquarePen class="h-5 w-5"/>
                            </a>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-muted-foreground block text-xs uppercase font-medium mb-1">Usuário</span>
                                <span class="text-foreground">{{ u.username }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground block text-xs uppercase font-medium mb-1">E-mail</span>
                                <span class="text-foreground">{{ u.email || u.username }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paginação -->
            <Pagination 
                :pagination-data="props.users"
                :current-per-page="Number(form.per_page)"
                @update:per-page="handlePerPageChange"
            />
        </div>
    </DashboardLayout>
</template>