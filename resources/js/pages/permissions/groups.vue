<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue'; // Ajuste o caminho se necessário
import { Head, Link } from '@inertiajs/vue3'; // Adicionado Link para possíveis botões
import { ref } from 'vue'; // Importar ref para dados reativos
import { Button } from '@/components/ui/button'; // Supondo que você tenha este componente
import { Pencil, Plus, SquarePen, Trash } from 'lucide-vue-next';
import { BreadcrumbItem } from '@/types';

interface Group {
    id: number;
    name: string;
    description: string;
    members: [];
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
                    <Link href="#">
                    <Plus class="mr-2 h-4 w-4" />
                    Novo Grupo
                    </Link>
                </Button>
            </div>

            <div class="bg-card p-0 sm:p-6 rounded-lg shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted/50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Grupo</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">
                                Descrição</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-card divide-y divide-border">
                        <tr v-if="props.groups.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-muted-foreground">
                                Nenhum grupo encontrado.
                            </td>
                        </tr>

                        <tr v-for="g in props.groups.data" :key="g.id" class="hover:bg-muted/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ g.name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ g.description
                                }}</td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3 flex items-center justify-end">
                                <a :href="route('groups.edit', g.id)" target="_blank"
                                    class="text-green-500 hover:text-green-500/60" title="Ver">
                                    <SquarePen />
                                </a>
                                <button class="text-destructive hover:text-destructive/80" title="Excluir">
                                    <Trash />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
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