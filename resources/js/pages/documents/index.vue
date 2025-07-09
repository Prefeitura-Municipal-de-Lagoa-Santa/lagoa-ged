<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Eye, FilePlus, Pencil, Plus, Trash } from 'lucide-vue-next';
import { BreadcrumbItem } from '@/types';

// Ajustando a interface Document para refletir a estrutura do seu MongoDB
interface Document {
  id: string; // MongoDB usa _id como string
  title: string; // Assumindo que você tem um campo 'title' no seu documento
  metadata: { // O campo metadata é um objeto
    document_type: string;
    // ... outras propriedades dentro de metadata
  };
  upload_date: string; // Data de upload, como string ISO
  file_extension: string; // Extensão do arquivo (PDF, DOCX, etc.)
  // ... outros campos que você pode ter, como 'status' (se for usar)
};

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
  filters?: Record<string, string>;
};

const props = defineProps<Props>();

const getTypeBadgeClass = (docType: string): string => {
  switch (docType?.toUpperCase()) {
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

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Página Inicial', href: route('dashboard') },
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
          <Link :href="route('documents.import')">
          <FilePlus class="mr-2 h-4 w-4" />
          Importar Documentos
          </Link>
        </Button>
      </div>

      <div class="bg-card p-0 sm:p-6 rounded-lg shadow-md overflow-x-auto hidden md:block">
        <table class="min-w-full divide-y divide-border">
          <thead class="bg-muted/60">
            <tr>
              <th scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Título
              </th>
              <th scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Tipo de
                Documento</th>
              <th scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Data de
                Upload</th>
              <th scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Formato
                do Arquivo</th>
              <th scope="col" class="relative px-6 py-3"><span class="sr-only">Ações</span></th>
            </tr>
          </thead>
          <tbody class="bg-card divide-y divide-border">
            <tr v-if="props.documents.data.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-sm text-muted-foreground">
                Nenhum documento encontrado.
              </td>
            </tr>

            <tr v-for="doc in props.documents.data" :key="doc.id" class="hover:bg-muted/50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">
                <a :href="route('documents.show', doc.id)">{{ doc.title }}</a>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="getTypeBadgeClass(doc.metadata.document_type)">
                  {{ doc.metadata.document_type }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">{{ new
                Date(doc.upload_date).toLocaleDateString() }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="getTypeBadgeClass(doc.file_extension)">
                  {{ doc.file_extension }}
                </span>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3 flex items-center justify-end">
                <a :href="route('documents.show', doc.id)" class="text-primary hover:text-primary/80" title="Ver">
                  <Eye />
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="md:hidden">
        <div v-if="props.documents.data.length === 0"
          class="bg-card p-6 rounded-lg shadow-md text-center text-sm text-muted-foreground">
          Nenhum documento encontrado.
        </div>
        <div v-else class="grid gap-4">
          <div v-for="doc in props.documents.data" :key="doc.id"
            class="bg-card p-4 rounded-lg shadow-md border border-border">
            <a :href="route('documents.view', doc.id)" target="_blank">
              <div class="flex justify-between items-start mb-2">
                <h3 class="text-lg font-semibold text-foreground break-words pr-2">
                  {{ doc.title }}
                </h3>
              </div>
              <div class="grid grid-cols-2 gap-y-2 gap-x-4 text-sm">
                <div>
                  <span class="text-muted-foreground block text-xs uppercase font-medium p-1">Tipo de Documento</span>
                  <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="getTypeBadgeClass(doc.metadata.document_type)">
                    {{ doc.metadata.document_type }}
                  </span>
                </div>
                <div>
                  <span class="text-muted-foreground block text-xs uppercase font-medium p-1">Formato do Arquivo</span>
                  <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="getTypeBadgeClass(doc.file_extension)">
                    {{ doc.file_extension }}
                  </span>
                </div>
                <div class="col-span-2">
                  <span class="text-muted-foreground block text-xs uppercase font-medium p-1">Data de Upload</span>
                  <span class="text-foreground p-1">{{ new Date(doc.upload_date).toLocaleDateString() }}</span>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
      <div v-if="props.documents.links.length > 3" class="mt-6 flex justify-center">
        <div class="flex flex-wrap -mb-1">
          <template v-for="(link, key) in props.documents.links" :key="key">
            <div v-if="link.url === null"
              class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-muted-foreground border rounded" v-html="link.label" />
            <Link v-else
              class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-muted focus:border-primary focus:text-primary transition-colors"
              :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }" :href="link.url"
              v-html="link.label" />
          </template>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>