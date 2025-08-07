<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginationData {
  data: any[];
  links: PaginationLink[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

interface Props {
  paginationData: PaginationData;
  perPageOptions?: number[];
  currentPerPage?: number;
  showPerPageSelector?: boolean;
  showResultsInfo?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  perPageOptions: () => [10, 25, 50, 100],
  currentPerPage: 25,
  showPerPageSelector: true,
  showResultsInfo: true,
});

const emit = defineEmits<{
  'update:perPage': [value: number];
}>();

const displayLinks = computed(() => {
  if (!props.paginationData.links) return [];
  
  // Remove "Previous" e "Next" e filtra apenas links numéricos
  return props.paginationData.links.filter((link, index) => {
    return index > 0 && index < props.paginationData.links.length - 1;
  });
});

const previousLink = computed(() => {
  if (!props.paginationData.links?.length) return null;
  return props.paginationData.links[0];
});

const nextLink = computed(() => {
  if (!props.paginationData.links?.length) return null;
  return props.paginationData.links[props.paginationData.links.length - 1];
});

const hasMultiplePages = computed(() => {
  return props.paginationData.last_page > 1;
});

const resultsText = computed(() => {
  const { from, to, total } = props.paginationData;
  if (total === 0) return 'Nenhum resultado encontrado';
  if (total === 1) return '1 resultado';
  return `Mostrando ${from} a ${to} de ${total} resultados`;
});

const handlePerPageChange = (value: number) => {
  emit('update:perPage', value);
};
</script>

<template>
  <div v-if="hasMultiplePages || showPerPageSelector || showResultsInfo" class="mt-8">
    <!-- Informações dos resultados e seletor de itens por página -->
    <div v-if="showResultsInfo || showPerPageSelector" class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
      <div v-if="showResultsInfo" class="text-sm text-gray-700 dark:text-gray-300">
        {{ resultsText }}
      </div>
      
      <div v-if="showPerPageSelector" class="flex items-center gap-2">
        <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
          Itens por página:
        </label>
        <select 
          id="perPage"
          :value="currentPerPage"
          @change="handlePerPageChange(Number(($event.target as HTMLSelectElement).value))"
          class="rounded-lg border-gray-300 dark:border-stone-700 dark:bg-stone-800 dark:text-white text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option v-for="option in perPageOptions" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
      </div>
    </div>

    <!-- Paginação -->
    <div v-if="hasMultiplePages" class="flex justify-center">
      <nav class="flex items-center gap-1" aria-label="Paginação">
        <!-- Botão Anterior -->
        <Link 
          v-if="previousLink?.url"
          :href="previousLink.url"
          class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-stone-900 border border-gray-200 dark:border-stone-700 rounded-lg hover:bg-gray-50 dark:hover:bg-stone-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
          aria-label="Página anterior"
        >
          <ChevronLeft class="h-4 w-4 mr-1" />
          Anterior
        </Link>
        <span 
          v-else
          class="flex items-center px-3 py-2 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-stone-800 border border-gray-200 dark:border-stone-700 rounded-lg cursor-not-allowed"
        >
          <ChevronLeft class="h-4 w-4 mr-1" />
          Anterior
        </span>

        <!-- Links das páginas -->
        <template v-for="(link, index) in displayLinks" :key="index">
          <Link 
            v-if="link.url"
            :href="link.url"
            class="px-3 py-2 text-sm font-medium border rounded-lg transition-colors focus:ring-2 focus:ring-blue-500"
            :class="{
              'bg-blue-600 text-white border-blue-600 hover:bg-blue-700': link.active,
              'text-gray-700 dark:text-gray-200 bg-white dark:bg-stone-900 border-gray-200 dark:border-stone-700 hover:bg-gray-50 dark:hover:bg-stone-800': !link.active
            }"
            :aria-label="`Página ${link.label}`"
            :aria-current="link.active ? 'page' : undefined"
            v-html="link.label"
          />
          <span 
            v-else
            class="px-3 py-2 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-stone-800 border border-gray-200 dark:border-stone-700 rounded-lg"
            v-html="link.label"
          />
        </template>

        <!-- Botão Próximo -->
        <Link 
          v-if="nextLink?.url"
          :href="nextLink.url"
          class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-stone-900 border border-gray-200 dark:border-stone-700 rounded-lg hover:bg-gray-50 dark:hover:bg-stone-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
          aria-label="Próxima página"
        >
          Próxima
          <ChevronRight class="h-4 w-4 ml-1" />
        </Link>
        <span 
          v-else
          class="flex items-center px-3 py-2 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-stone-800 border border-gray-200 dark:border-stone-700 rounded-lg cursor-not-allowed"
        >
          Próxima
          <ChevronRight class="h-4 w-4 ml-1" />
        </span>
      </nav>
    </div>
  </div>
</template>
