<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Eye, FilePlus, Pencil, Plus, Trash, ChevronDown, ChevronUp, SquarePen, Filter, X } from 'lucide-vue-next';
import { BreadcrumbItem } from '@/types';
import Pagination from '@/components/ui/Pagination.vue';

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
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
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
  per_page: props.filters?.per_page || 25,
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

const handlePerPageChange = (perPage: number) => {
  // Atualiza diretamente sem esperar o watcher
  const newFilters = {
    ...form.value,
    per_page: perPage
  };
  
  // Remove valores vazios
  const cleanForm = Object.fromEntries(
    Object.entries(newFilters).filter(([, value]) => value !== '' && value !== null && value !== 0)
  );
  
  router.get(route('documents.index'), cleanForm, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      form.value.per_page = perPage;
    }
  });
};

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
    <div class="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
            Documentos
          </h1>
          <p class="text-gray-500 dark:text-gray-400 text-base">Gerencie e visualize todos os documentos do sistema.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <Button @click="showFilters = !showFilters" variant="outline" class="flex items-center gap-2 px-4 py-2 rounded-xl font-semibold transition shadow">
            <Filter class="h-4 w-4" />
            Filtros
            <ChevronUp v-if="showFilters" class="h-4 w-4" />
            <ChevronDown v-else class="h-4 w-4" />
          </Button>
          
          <Button v-if="props.user.is_admin" as-child class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-xl font-semibold shadow transition">
            <Link :href="route('documents.import')" class="flex items-center gap-2">
              <FilePlus class="h-4 w-4" />
              Importar Documentos
            </Link>
          </Button>
        </div>
      </div>

      <div v-if="showFilters" class="mb-8 p-6 border rounded-2xl shadow-xl bg-white dark:bg-stone-950/95 border-gray-200 dark:border-stone-800 transition-all duration-300 ease-in-out">
        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Filtros</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <div>
            <label for="title" class="block text-base font-semibold text-gray-700 dark:text-gray-200 mb-2">Título</label>
            <input type="text" id="title" v-model="form.title"
              class="block w-full rounded-xl border-gray-300 dark:bg-stone-800 dark:border-stone-700 dark:text-white px-4 py-2 text-base shadow focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>
          <div>
            <label for="tags" class="block text-base font-semibold text-gray-700 dark:text-gray-200 mb-2">Tags (separadas por vírgulas)</label>
            <input type="text" id="tags" v-model="form.tags"
              class="block w-full rounded-xl border-gray-300 dark:bg-stone-800 dark:border-stone-700 dark:text-white px-4 py-2 text-base shadow focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>
          <div>
            <label for="document_year" class="block text-base font-semibold text-gray-700 dark:text-gray-200 mb-2">Ano do Documento</label>
            <select id="document_year" v-model="form.document_year"
              class="block w-full rounded-xl border-gray-300 dark:bg-stone-800 dark:border-stone-700 dark:text-white px-4 py-2 text-base shadow focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="">Todos os Anos</option>
              <option v-for="year in props.years" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>
          <div>
            <label for="other_metadata" class="block text-base font-semibold text-gray-700 dark:text-gray-200 mb-2">Outros Metadados</label>
            <input type="text" id="other_metadata" v-model="form.other_metadata"
              class="block w-full rounded-xl border-gray-300 dark:bg-stone-800 dark:border-stone-700 dark:text-white px-4 py-2 text-base shadow focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Buscar em Fornecedor, Paciente, etc.">
          </div>
        </div>
        <div class="mt-6 flex justify-end">
          <Button @click="form = { title: '', tags: '', document_year: '', other_metadata: '', per_page: 25 }; applyFilters();" variant="outline" class="flex items-center gap-2 px-4 py-2 rounded-xl font-semibold transition shadow">
            <X class="h-4 w-4" />
            Limpar Filtros
          </Button>
        </div>
      </div>

      <div class="overflow-x-auto bg-white dark:bg-stone-800 rounded-2xl shadow-xl hidden md:block">
        <table class="min-w-full">
          <thead class="bg-gray-50 dark:bg-stone-900/80">
            <tr>
              <th scope="col"
                class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">TÍTULO
              </th>
              <th scope="col"
                class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">TIPO DE
                DOCUMENTO</th>
              <th scope="col"
                class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">DATA DE
                UPLOAD</th>
              <th scope="col"
                class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">FORMATO
                DO ARQUIVO</th>
              <th scope="col" class="relative px-6 py-4 text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider text-center">AÇÕES</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-stone-950/95 divide-y divide-gray-200 dark:divide-stone-700">
            <tr v-if="props.documents.data.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-base text-gray-500 dark:text-gray-400">
                Nenhum documento encontrado.
              </td>
            </tr>

            <tr v-for="doc in props.documents.data" :key="doc.id" class="hover:bg-gray-50 dark:hover:bg-stone-900/60 transition-colors">
              <td class="px-6 py-4 text-base font-semibold text-gray-900 dark:text-white">
                <a :href="route('documents.show', doc.id)" class="hover:text-blue-600 dark:hover:text-blue-400 transition">{{ doc.title }}</a>
              </td>
              <td class="px-6 py-4 text-base text-gray-700 dark:text-gray-300">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                  {{ doc.metadata?.document_type }}
                </span>
              </td>
              <td class="px-6 py-4 text-base text-gray-700 dark:text-gray-300">{{ new
                Date(doc.upload_date).toLocaleDateString() }}</td>
              <td class="px-6 py-4 text-base">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                  {{ doc.file_extension }}
                </span>
              </td>
              <td class="px-6 py-4 text-base font-medium flex items-center justify-center space-x-3">
                <a :href="route('documents.edit', doc.id)" class="text-green-600 dark:text-green-400 hover:text-green-500 transition p-1 rounded" title="Editar">
                  <SquarePen class="h-5 w-5"/>
                </a>
                <a :href="route('documents.show', doc.id)" class="text-blue-600 dark:text-blue-400 hover:text-blue-500 transition p-1 rounded" title="Ver">
                  <Eye class="h-5 w-5" />
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="md:hidden">
        <div v-if="props.documents.data.length === 0"
          class="bg-white dark:bg-stone-950/95 p-8 rounded-2xl shadow-xl text-center text-base text-gray-500 dark:text-gray-400">
          Nenhum documento encontrado.
        </div>
        <div v-else class="grid gap-6">
          <div v-for="doc in props.documents.data" :key="doc.id"
            class="bg-white dark:bg-stone-950/95 p-6 rounded-2xl shadow-xl ">
            <a :href="route('documents.show', doc.id)">
              <div class="flex justify-between items-start mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white break-words pr-2">
                  {{ doc.title }}
                </h3>
              </div>
              <div class="grid grid-cols-2 gap-y-4 gap-x-4 text-base">
                <div>
                  <span class="text-gray-600 dark:text-gray-400 block text-xs uppercase font-semibold mb-1">Tipo de Documento</span>
                  <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full"
                    :class="getTypeBadgeClass(doc.metadata?.document_type)">
                    {{ doc.metadata?.document_type }}
                  </span>
                </div>
                <div>
                  <span class="text-gray-600 dark:text-gray-400 block text-xs uppercase font-semibold mb-1">Formato do Arquivo</span>
                  <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full"
                    :class="getTypeBadgeClass(doc.file_extension)">
                    {{ doc.file_extension }}
                  </span>
                </div>
                <div class="col-span-2">
                  <span class="text-gray-600 dark:text-gray-400 block text-xs uppercase font-semibold mb-1">Data de Upload</span>
                  <span class="text-gray-900 dark:text-gray-200">{{ new Date(doc.upload_date).toLocaleDateString() }}</span>
                </div>
                <div v-if="doc.metadata?.document_year" class="col-span-2">
                  <span class="text-gray-600 dark:text-gray-400 block text-xs uppercase font-semibold mb-1">Ano do Documento</span>
                  <span class="text-gray-900 dark:text-gray-200">{{ doc.metadata.document_year }}</span>
                </div>
                <template v-for="(value, key) in doc.metadata" :key="String(key)">
                  <div v-if="!['document_type', 'document_year'].includes(String(key)) && value" class="col-span-2">
                    <span class="text-gray-600 dark:text-gray-400 block text-xs uppercase font-semibold mb-1">{{ String(key).replace(/_/g, ' ') }}</span>
                    <span class="text-gray-900 dark:text-gray-200">{{ value }}</span>
                  </div>
                </template>
              </div>
              <div class="flex justify-end gap-3 mt-4 pt-4">
                <a :href="route('documents.edit', doc.id)" class="text-green-600 dark:text-green-400 hover:text-green-500 transition p-2 rounded-xl" title="Editar">
                  <SquarePen class="h-5 w-5"/>
                </a>
                <a :href="route('documents.show', doc.id)" class="text-blue-600 dark:text-blue-400 hover:text-blue-500 transition p-2 rounded-xl" title="Ver">
                  <Eye class="h-5 w-5" />
                </a>
              </div>
            </a>
          </div>
        </div>
      </div>
      
      <!-- Paginação -->
      <Pagination 
        :pagination-data="props.documents"
        :current-per-page="Number(form.per_page)"
        @update:per-page="handlePerPageChange"
      />
    </div>
  </DashboardLayout>
</template>