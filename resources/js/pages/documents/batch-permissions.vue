<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import GroupManagerModal from '@/components/modals/GroupManagerModal.vue';
import { Users, Filter, X, CheckCircle, ChevronDown, ChevronUp } from 'lucide-vue-next';


interface Document {
  id: string;
  title: string;
  metadata: Record<string, any>;
}
interface PaginatedDocuments {
  data: Document[];
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
}
interface Props {
  documents: PaginatedDocuments;
  groups: Array<{ id: string; name: string }>;
  filters: Record<string, string | number | null>;
  years: number[];
}
const props = defineProps<Props>();

const selectedDocuments = ref<string[]>([]);
const selectedReadGroups = ref<string[]>([]);
const selectedWriteGroups = ref<string[]>([]);
const showReadGroupModal = ref(false);
const showWriteGroupModal = ref(false);
const showPreview = ref(false);
const previewData = ref<any>(null);

const allSelected = computed(() => selectedDocuments.value.length === props.documents.data.length);

function toggleAll() {
  if (allSelected.value) {
    selectedDocuments.value = [];
  } else {
    selectedDocuments.value = props.documents.data.map(d => d.id);
  }
}
// Filtros
const form = ref({
  title: props.filters?.title || '',
  tags: props.filters?.tags || '',
  document_year: props.filters?.document_year || '',
  other_metadata: props.filters?.other_metadata || '',
});

const areFiltersActive = () => {
  return Object.values(form.value).some(value => value !== '' && value !== null);
};

const showFilters = ref(areFiltersActive());

const applyFilters = () => {
  const cleanForm = Object.fromEntries(
    Object.entries(form.value).filter(([, value]) => value !== '' && value !== null)
  );
  router.get(route('documents.batch-permissions'), cleanForm, {
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

function submitBatch() {
  router.post(route('documents.batch-permissions'), {
    document_ids: selectedDocuments.value,
    read_group_ids: selectedReadGroups.value,
    write_group_ids: selectedWriteGroups.value,
  }, {
    onSuccess: () => {
      selectedDocuments.value = [];
      selectedReadGroups.value = [];
      selectedWriteGroups.value = [];
    }
  });
}

async function previewChanges() {
  if (!selectedDocuments.value.length) return;
  
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
      console.error('CSRF token não encontrado');
      return;
    }

    const response = await fetch(route('documents.batch-permissions'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        document_ids: selectedDocuments.value,
        read_group_ids: selectedReadGroups.value,
        write_group_ids: selectedWriteGroups.value,
        preview: true,
      })
    });
    
    if (response.ok) {
      previewData.value = await response.json();
      showPreview.value = true;
    } else {
      const errorText = await response.text();
      console.error('Erro no preview:', response.status, errorText);
    }
  } catch (error) {
    console.error('Erro ao buscar preview:', error);
  }
}

function getGroupNames(groupIds: string[]) {
  if (!previewData.value) return '';
  return groupIds.map(id => {
    const group = previewData.value.groups.find((g: any) => g.id === id || g._id === id);
    return group ? group.name : id;
  }).join(', ');
}
</script>

<template>
  <Head title="Permissões em Lote" />
  <DashboardLayout>
    <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-2xl md:text-3xl font-semibold text-foreground">Editar Permissões em Lote</h1>
        <div class="flex flex-wrap gap-2">
          <Button @click="showFilters = !showFilters" variant="outline" class="flex items-center gap-2">
            <Filter class="h-4 w-4" />
            Filtros
            <ChevronUp v-if="showFilters" class="h-4 w-4" />
            <ChevronDown v-else class="h-4 w-4" />
          </Button>
          <Button @click="showReadGroupModal = true" class="flex items-center gap-2">
            <Users class="h-4 w-4" />
            Grupos de Leitura
            <span v-if="selectedReadGroups.length" class="ml-1 px-2 py-0.5 bg-white/20 rounded-full text-xs">
              {{ selectedReadGroups.length }}
            </span>
          </Button>
          <Button @click="showWriteGroupModal = true" class="flex items-center gap-2">
            <Users class="h-4 w-4" />
            Grupos de Escrita
            <span v-if="selectedWriteGroups.length" class="ml-1 px-2 py-0.5 bg-white/20 rounded-full text-xs">
              {{ selectedWriteGroups.length }}
            </span>
          </Button>
          <Button 
            :disabled="!selectedDocuments.length" 
            @click="previewChanges"
            variant="outline"
            class="flex items-center gap-2"
          >
            <CheckCircle class="h-4 w-4" />
            Visualizar Mudanças
          </Button>
          <Button 
            :disabled="!selectedDocuments.length || (!selectedReadGroups.length && !selectedWriteGroups.length)" 
            @click="submitBatch"
            class="flex items-center gap-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400"
          >
            <CheckCircle class="h-4 w-4" />
            Atualizar Permissões
            <span v-if="selectedDocuments.length && (selectedReadGroups.length || selectedWriteGroups.length)" class="ml-1 px-2 py-0.5 bg-white/20 rounded-full text-xs">
              {{ selectedDocuments.length }}
            </span>
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

      <div class="overflow-x-auto bg-gray-900 rounded-lg shadow-md">
        <table class="min-w-full text-white">
          <thead class="bg-zinc-700">
            <tr class="border-b border-gray-700">
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">
                <input type="checkbox" :checked="allSelected" @change="toggleAll" class="rounded border-gray-600 bg-gray-700" />
              </th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">TÍTULO</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">TIPO DE DOCUMENTO</th>
            </tr>
          </thead>
          <tbody class="bg-stone-950 divide-y divide-gray-700">
            <tr v-for="doc in props.documents.data" :key="doc.id" class="hover:bg-gray-800 transition-colors">
              <td class="px-6 py-4">
                <input type="checkbox" :value="doc.id" v-model="selectedDocuments" class="rounded border-gray-600 bg-gray-700" />
              </td>
              <td class="px-6 py-4 text-sm font-medium text-white">{{ doc.title }}</td>
              <td class="px-6 py-4 text-sm text-gray-300">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ doc.metadata?.document_type || '-' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
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

      <div class="mt-6 text-sm text-muted-foreground">
        <span v-if="selectedDocuments.length > 0">
          {{ selectedDocuments.length }} documento(s) selecionado(s)
        </span>
        <span v-else>
          Nenhum documento selecionado
        </span>
      </div>

      <GroupManagerModal
        v-model:modelValue="showReadGroupModal"
        :allGroups="props.groups"
        :initialSelectedGroups="props.groups.filter(g => selectedReadGroups.includes(g.id))"
        username=""
        @confirm="groups => { selectedReadGroups = groups.map(g => g.id); showReadGroupModal = false; }"
      />
      
      <GroupManagerModal
        v-model:modelValue="showWriteGroupModal"
        :allGroups="props.groups"
        :initialSelectedGroups="props.groups.filter(g => selectedWriteGroups.includes(g.id))"
        username=""
        @confirm="groups => { selectedWriteGroups = groups.map(g => g.id); showWriteGroupModal = false; }"
      />

      <!-- Modal de Preview -->
      <div v-if="showPreview" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-4xl max-h-96 overflow-y-auto">
          <h3 class="text-lg font-semibold mb-4">Visualizar Mudanças</h3>
          <div v-if="previewData">
            <p class="mb-4">
              <strong>{{ previewData.documents_with_changes }}</strong> de <strong>{{ previewData.total_documents }}</strong> documentos terão permissões alteradas.
            </p>
            <div v-for="change in previewData.changes" :key="change.document_id" class="mb-4 p-3 border rounded">
              <h4 class="font-medium">{{ change.document_title }}</h4>
              <div v-if="change.read_groups_to_add.length" class="text-green-600">
                ✓ Adicionados à leitura: {{ getGroupNames(change.read_groups_to_add) }}
              </div>
              <div v-if="change.read_groups_to_remove.length" class="text-red-600">
                ✗ Removidos da leitura: {{ getGroupNames(change.read_groups_to_remove) }}
              </div>
              <div v-if="change.write_groups_to_add.length" class="text-green-600">
                ✓ Adicionados à escrita: {{ getGroupNames(change.write_groups_to_add) }}
              </div>
              <div v-if="change.write_groups_to_remove.length" class="text-red-600">
                ✗ Removidos da escrita: {{ getGroupNames(change.write_groups_to_remove) }}
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-4">
            <Button @click="showPreview = false" variant="outline">Fechar</Button>
            <Button @click="showPreview = false; submitBatch()" class="bg-green-600 hover:bg-green-700">Confirmar Alterações</Button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
