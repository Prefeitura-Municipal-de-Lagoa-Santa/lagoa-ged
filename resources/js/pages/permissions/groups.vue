<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue'; // Ajuste o caminho se necessário
import { Head, Link, router } from '@inertiajs/vue3'; // Adicionado Link para possíveis botões
import { ref } from 'vue'; // Importar ref para dados reativos
import { Button } from '@/components/ui/button'; // Supondo que você tenha este componente
import { ListPlus, SquarePen, Trash } from 'lucide-vue-next';
import { BreadcrumbItem } from '@/types';

interface Group {
    id: number;
    name: string;
    description: string;
    members: [];
    is_protected: boolean;
};

// Interface para o objeto de paginação que o Laravel envia
interface PaginatedGroups {
    data: Group[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    // Você pode adicionar outras propriedades de paginação se precisar, ex: total, current_page
};

interface Props {
    groups: PaginatedGroups;
    filters?: Record<string, string>; // Um objeto para os filtros (opcional por enquanto)
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pagina Inicial', href: route('dashboard') },
    { title: 'Permissões' },
    { title: 'Grupos', href: route('groups.index') }
];

const confirmDelete = (groupId: number) => {
    // É uma prática essencial pedir confirmação para ações destrutivas!
    if (confirm('Tem certeza que deseja excluir este grupo? Esta ação não pode ser desfeita.')) {
        // Usa o router do Inertia para enviar uma requisição POST para a rota de exclusão
        router.post(route('groups.destroy', groupId), {
            preserveScroll: true // Opcional: não rola a página para o topo após a exclusão
        });
    }
};


</script>

<template>

    <Head title="Documentos" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h1 class="text-2xl md:text-3xl font-semibold text-foreground">
                    Grupos
                </h1>
                <Button as-child>
                    <Link :href="route('groups.create')">
                    <ListPlus class="mr-2 h-4 w-4" />
                    Novo Grupo
                    </Link>
                </Button>
            </div>

            <div class="overflow-x-auto bg-gray-900 rounded-lg shadow-md hidden md:block">
                <table class="min-w-full text-white">
                    <thead class="bg-gray-500 dark:bg-zinc-700">
                        <tr class="border-b border-gray-700">
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-white dark:text-gray-300 uppercase tracking-wider">
                                GRUPO</th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-semibold text-white dark:text-gray-300 uppercase tracking-wider">
                                DESCRIÇÃO</th>
                            <th scope="col" class="relative px-6 py-4 text-xs font-semibold text-white dark:text-gray-300 uppercase tracking-wider text-center">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-50 dark:bg-stone-950 divide-y divide-gray-700">
                        <tr v-if="props.groups.data.length === 0">
                            <td colspan="3" class="px-6 py-12 text-center text-sm text-gray-800">
                                Nenhum grupo encontrado.
                            </td>
                        </tr>

                        <tr v-for="g in props.groups.data" :key="g.id" class="hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-700 dark:text-white">{{ g.name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400">{{ g.description
                            }}</td>
                            <td class="px-6 py-4 text-sm font-medium flex items-center justify-center space-x-3">
                                <a :href="route('groups.edit', g.id)" class="text-green-600 dark:text-green-400 hover:text-green-300"
                                    title="Editar">
                                    <SquarePen class="h-5 w-5" />
                                </a>
                                <button @click="confirmDelete(g.id)" v-if="!g.is_protected"
                                    class="text-red-600 dark:text-red-400 hover:text-red-300" title="Excluir">
                                    <Trash class="h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Versão Mobile -->
            <div class="md:hidden">
                <div v-if="props.groups.data.length === 0"
                    class="bg-card p-6 rounded-lg shadow-md text-center text-sm text-muted-foreground">
                    Nenhum grupo encontrado.
                </div>
                <div v-else class="grid gap-4">
                    <div v-for="g in props.groups.data" :key="g.id"
                        class="bg-card p-4 rounded-lg shadow-md border border-border">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1 pr-2">
                                <h3 class="text-lg font-semibold text-foreground break-words mb-1">
                                    {{ g.name }}
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    {{ g.description }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2 ml-2">
                                <a :href="route('groups.edit', g.id)" 
                                    class="text-green-600 dark:text-green-400 hover:text-green-300 p-2 rounded-md hover:bg-green-50 dark:hover:bg-green-900/20" 
                                    title="Editar">
                                    <SquarePen class="h-5 w-5" />
                                </a>
                                <button @click="confirmDelete(g.id)" v-if="!g.is_protected"
                                    class="text-red-600 dark:text-red-400 hover:text-red-300 p-2 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20" 
                                    title="Excluir">
                                    <Trash class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                        <div v-if="g.is_protected" class="mt-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                Protegido
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="props.groups.links.length > 3" class="mt-6 flex justify-center">
                <div class="flex flex-wrap -mb-1">
                    <template v-for="(link, key) in props.groups.links" :key="key">
                        <div v-if="link.url === null"
                            class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-muted-foreground border rounded"
                            v-html="link.label" />
                        <Link v-else
                            class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-muted focus:border-primary focus:text-primary transition-colors"
                            :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
                            :href="link.url" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>