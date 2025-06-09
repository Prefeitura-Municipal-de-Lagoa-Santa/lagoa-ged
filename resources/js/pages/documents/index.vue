<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue'; // Ajuste o caminho se necessário
import { Head, Link } from '@inertiajs/vue3'; // Adicionado Link para possíveis botões
import { ref } from 'vue'; // Importar ref para dados reativos
import { Button } from '@/components/ui/button'; // Supondo que você tenha este componente
import { Eye, Pencil, Plus, Trash } from 'lucide-vue-next';
import { BreadcrumbItem } from '@/types';

interface Document {
  id: number;
  name: string;
  type: string;
  size: string;
  uploaded_at: string; // O Laravel geralmente envia datas como strings ISO 8601
  status: string;
  file_name: string; // O nome do arquivo real para o link de visualização
};

// Interface para o objeto de paginação que o Laravel envia
interface PaginatedDocuments {
  data: Document[];
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
  // Você pode adicionar outras propriedades de paginação se precisar, ex: total, current_page
};

interface Props {
  documents: PaginatedDocuments;
  filters?: Record<string, string>; // Um objeto para os filtros (opcional por enquanto)
};

const props = defineProps<Props>();

const getTypeBadgeClass = (docType: string): string => {
  switch (docType?.toUpperCase()) { // toUpperCase para ser mais robusto
    case 'PDF':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    case 'DOCX':
      return 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200';
    case 'XLSX':
      return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200';
    case 'PPTX':
      return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
    default:
      return 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200';
  }
};

const getStatusBadgeClass = (status: string): string => {
  switch (status) {
    case 'Ativo':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    case 'Em revisão':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    case 'Arquivado':
      return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    default:
      return 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200';
  }
};

const breadcrumbs:BreadcrumbItem[] = [
    { title: 'Pagina Inicial', href: route('dashboard') }, 
    { title: 'Documentos', href: route('documents.index') } 
];


</script>

<template>
    <Head title="Documentos" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h1 class="text-2xl md:text-3xl font-semibold text-foreground">
                    Documentos 
                </h1>
                <Button as-child>
                    <Link href="#"> <Plus class="mr-2 h-4 w-4"/>
                        Novo Documento
                    </Link>
                </Button>
            </div>

            <div class="bg-card p-0 sm:p-6 rounded-lg shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Nome do Documento</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tamanho</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Data de Upload</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-card divide-y divide-border">
                        <tr v-if="props.documents.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-muted-foreground">
                                Nenhum documento encontrado.
                            </td>
                        </tr>

                        <tr v-for="doc in props.documents.data" :key="doc.id" class="hover:bg-muted/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">{{ doc.title }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getTypeBadgeClass(doc.file_extension)">
                                    {{ doc.file_extension }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ doc.file_size }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ new Date(doc.upload_date).toLocaleDateString() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusBadgeClass(doc.status)">
                                    {{ doc.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3 flex items-center justify-end">
                                <a href="#" target="_blank" class="text-primary hover:text-primary/80" title="Ver"><Eye/></a>
                                <a class="text-amber-600 hover:text-amber-600/80" title="Editar"><Pencil/></a>
                                <button class="text-destructive hover:text-destructive/80" title="Excluir"><Trash/></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="props.documents.links.length > 3" class="mt-6 flex justify-center">
                 <div class="flex flex-wrap -mb-1">
                    <template v-for="(link, key) in props.documents.links" :key="key">
                        <div v-if="link.url === null"
                             class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-muted-foreground border rounded"
                             v-html="link.label" />
                        <Link v-else
                              class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-muted focus:border-primary focus:text-primary transition-colors"
                              :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
                              :href="link.url"
                              v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>