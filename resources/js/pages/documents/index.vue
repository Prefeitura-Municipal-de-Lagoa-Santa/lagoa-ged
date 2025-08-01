<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Eye, FilePlus, Pencil, Plus, Trash, ChevronDown, ChevronUp, SquarePen, Filter, X } from 'lucide-vue-next';
import { BreadcrumbItem } from '@/types';

interface Document {
  id: string;
  title: string;
  metadata: {
    document_type?: string;
    document_year?: number;
    [key: string]: any;
  };
  upload_date: string;
  file_extension: string;
};

interface User {
    id: string;
    full_name: string;
    username: string;
    email: string;
    is_protected: boolean;
    is_ldap: boolean;
    is_admin: boolean;
}

interface PaginatedDocuments {
  data: Document[];
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
};

interface Props {
  documents: PaginatedDocuments;
  filters: Record<string, string | number | null>;
  years: number[];
  user: User;
};

const props = defineProps<Props>();

const form = ref({
  title: props.filters?.title || '',
  tags: props.filters?.tags || '',
  document_year: props.filters?.document_year || '',
  other_metadata: props.filters?.other_metadata || '',
});

// Calcula se algum filtro está ativo para decidir se a caixa deve iniciar aberta
const areFiltersActive = () => {
  return Object.values(form.value).some(value => value !== '' && value !== null);
};

// O estado inicial de showFilters agora depende de ter filtros ativos na URL
const showFilters = ref(areFiltersActive());

const applyFilters = () => {
  const cleanForm = Object.fromEntries(
    Object.entries(form.value).filter(([, value]) => value !== '' && value !== null)
  );

  router.get(route('documents.index'), cleanForm, {
    preserveState: true,
    preserveScroll: true,
  });
};

let debounceTimeout: ReturnType<typeof setTimeout> | null = null;
watch(form, (newForm) => {
  if (debounceTimeout) {
    clearTimeout(debounceTimeout);
  }
  debounceTimeout = setTimeout(() => {
    applyFilters();
  }, 300);
}, { deep: true });

const getTypeBadgeClass = (docType?: string): string => {
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
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl md:text-3xl font-semibold text-foreground">
          Documentos
        </h1>
        <div class="flex flex-wrap gap-2">
          <Button @click="showFilters = !showFilters" variant="outline" class="flex items-center gap-2">
            <Filter class="h-4 w-4" />
            Filtros
            <ChevronUp v-if="showFilters" class="h-4 w-4" />
            <ChevronDown v-else class="h-4 w-4" />
          </Button>
          
          <Button v-if="props.user.is_admin" as-child class="bg-blue-600 text-white hover:bg-blue-700">
            <Link :href="route('documents.import')" class="flex items-center gap-2">
              <FilePlus class="h-4 w-4" />
              Importar Documentos
            </Link>
          </Button>
        </div>
      </div>

      <div v-if="showFilters" class="mb-6 p-4 border rounded-lg shadow-md bg-card transition-all duration-300 ease-in-out">
        <h2 class="text-xl font-semibold mb-3 text-foreground">Filtros</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <div>
            <label for="title" class="block text-sm font-medium text-muted-foreground">Título</label>
            <input type="text" id="title" v-model="form.title"
              class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:ring-primary">
          </div>
          <div>
            <label for="tags" class="block text-sm font-medium text-muted-foreground">Tags (separadas por vírgulas)</label>
            <input type="text" id="tags" v-model="form.tags"
              class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:ring-primary">
          </div>
          <div>
            <label for="document_year" class="block text-sm font-medium text-muted-foreground">Ano do Documento</label>
            <select id="document_year" v-model="form.document_year"
              class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:ring-primary">
              <option value="">Todos os Anos</option>
              <option v-for="year in props.years" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>
          <div>
            <label for="other_metadata" class="block text-sm font-medium text-muted-foreground">Outros Metadados</label>
            <input type="text" id="other_metadata" v-model="form.other_metadata"
              class="mt-1 block w-full rounded-md border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:ring-primary"
              placeholder="Buscar em Fornecedor, Paciente, etc.">
          </div>
        </div>
        <div class="mt-4 flex justify-end">
          <Button @click="form = { title: '', tags: '', document_year: '', other_metadata: '' }; applyFilters();" variant="outline" class="flex items-center gap-2">
            <X class="h-4 w-4" />
            Limpar Filtros
          </Button>
        </div>
      </div>

      <div class="overflow-x-auto bg-gray-900 rounded-lg shadow-md hidden md:block">
        <table class="min-w-full text-white">
          <thead class="bg-gray-500 dark:bg-zinc-700">
            <tr class="border-b border-gray-700">
              <th scope="col"
                class="px-6 py-4 text-left text-xs font-semibold text-white dark:text-gray-300 uppercase tracking-wider">TÍTULO
              </th>
              <th scope="col"
                class="px-6 py-4 text-left text-xs font-semibold text-white dark:text-gray-300 uppercase tracking-wider">TIPO DE
                DOCUMENTO</th>
              <th scope="col"
                class="px-6 py-4 text-left text-xs font-semibold text-white dark:text-gray-300 uppercase tracking-wider">DATA DE
                UPLOAD</th>
              <th scope="col"
                class="px-6 py-4 text-left text-xs font-semibold text-white dark:text-gray-300 uppercase tracking-wider">FORMATO
                DO ARQUIVO</th>
              <th scope="col" class="relative px-6 py-4 text-xs font-semibold text-white dark:text-gray-300 uppercase tracking-wider text-center">AÇÕES</th>
            </tr>
          </thead>
          <tbody class="bg-gray-50 dark:bg-stone-950 divide-y divide-gray-700">
            <tr v-if="props.documents.data.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-800">
                Nenhum documento encontrado.
              </td>
            </tr>

            <tr v-for="doc in props.documents.data" :key="doc.id" class="hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
              <td class="px-6 py-4 text-sm font-medium text-gray-700 dark:text-white">
                <a :href="route('documents.show', doc.id)" class="hover:text-blue-400">{{ doc.title }}</a>
              </td>
              <td class="px-6 py-4 text-sm text-gray-300">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ doc.metadata?.document_type }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400">{{ new
                Date(doc.upload_date).toLocaleDateString() }}</td>
              <td class="px-6 py-4 text-sm text-gray-700">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-900 text-blue-300">
                  {{ doc.file_extension }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm font-medium flex items-center justify-center space-x-3">
                <a :href="route('documents.edit', doc.id)" class="text-green-600 dark:text-green-400 hover:text-green-300" title="Editar">
                  <SquarePen class="h-5 w-5"/>
                </a>
                <a :href="route('documents.show', doc.id)" class="text-blue-600 dark:text-blue-500 hover:text-blue-400" title="Ver">
                  <Eye class="h-5 w-5" />
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
                    :class="getTypeBadgeClass(doc.metadata?.document_type)">
                    {{ doc.metadata?.document_type }}
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
                <div v-if="doc.metadata?.document_year" class="col-span-2">
                  <span class="text-muted-foreground block text-xs uppercase font-medium p-1">Ano do Documento</span>
                  <span class="text-foreground p-1">{{ doc.metadata.document_year }}</span>
                </div>
                <div v-for="(value, key) in doc.metadata" :key="key" v-if="!['document_type', 'document_year'].includes(key) && value">
                    <span class="text-muted-foreground block text-xs uppercase font-medium p-1">{{ key.replace(/_/g, ' ') }}</span>
                    <span class="text-foreground p-1">{{ value }}</span>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
      <div v-if="props.documents.links && props.documents.links.length > 3" class="mt-6 flex justify-center">
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