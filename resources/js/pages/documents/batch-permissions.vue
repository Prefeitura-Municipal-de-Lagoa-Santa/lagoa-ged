<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';
import GroupManagerModal from '@/components/modals/GroupManagerModal.vue';
import { Users, Filter, X, CheckCircle, ChevronDown, ChevronUp } from 'lucide-vue-next';
import axios from 'axios';


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
const showDetailedChanges = ref(false);
const jobNotification = ref<any>(null);
const showNotification = ref(false);

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
  // Primeiro, mostra o preview
  previewChanges();
}

function applyChanges() {
  // Aplicar as mudanças sem preview
  router.post(route('documents.batch-permissions'), {
    document_ids: selectedDocuments.value,
    read_group_ids: selectedReadGroups.value,
    write_group_ids: selectedWriteGroups.value,
  }, {
    onSuccess: () => {
      selectedDocuments.value = [];
      selectedReadGroups.value = [];
      selectedWriteGroups.value = [];
      showPreview.value = false;
      showDetailedChanges.value = false;
      
      // Iniciar polling para verificar notificações do job
      startNotificationPolling();
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

// Função para verificar notificações de jobs
async function checkNotifications() {
  try {
    const response = await axios.get('/documents/notifications');
    if (response.data && response.data.success && response.data.notification) {
      jobNotification.value = response.data.notification;
      showNotification.value = true;
      
      // Parar o polling quando receber uma notificação
      stopNotificationPolling();
      
      // Auto-hide notification after 10 seconds
      setTimeout(() => {
        showNotification.value = false;
      }, 10000);
    }
  } catch (error) {
    console.error('Erro ao verificar notificações:', error);
  }
}

// Função para fechar notificação manualmente
function closeNotification() {
  showNotification.value = false;
  jobNotification.value = null;
}

// Polling para verificar notificações (check a cada 3 segundos quando há job rodando)
let notificationInterval: number | null = null;

function startNotificationPolling() {
  if (notificationInterval) return; // Já está rodando
  
  notificationInterval = setInterval(() => {
    checkNotifications();
  }, 3000); // Check a cada 3 segundos
}

function stopNotificationPolling() {
  if (notificationInterval) {
    clearInterval(notificationInterval);
    notificationInterval = null;
  }
}

// Limpar polling quando o componente for desmontado
onUnmounted(() => {
  stopNotificationPolling();
});
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
          <thead class="bg-gray-500 dark:bg-zinc-700">
            <tr class="border-b border-gray-700">
              <th class="px-6 py-4 text-left text-xs font-semibold text-white dark:text-gray-300 uppercase tracking-wider">
                <input type="checkbox" :checked="allSelected" @change="toggleAll" class="rounded border-gray-600 bg-gray-700" />
              </th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-white dark:text-gray-300 uppercase tracking-wider">TÍTULO</th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-white dark:text-gray-300 uppercase tracking-wider">TIPO DE DOCUMENTO</th>
            </tr>
          </thead>
          <tbody class="bg-gray-50 dark:bg-stone-950 divide-y divide-gray-700">
            <tr v-for="doc in props.documents.data" :key="doc.id" class="hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
              <td class="px-6 py-4">
                <input type="checkbox" :value="doc.id" v-model="selectedDocuments" class="rounded border-gray-600 bg-gray-700" />
              </td>
              <td class="px-6 py-4 text-sm font-medium text-gray-700 dark:text-white">{{ doc.title }}</td>
              <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400">
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
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-5xl max-h-[80vh] overflow-y-auto">
          <div class="flex items-center mb-4">
            <h3 class="text-lg font-semibold">Confirmar Alterações em Lote</h3>
            <span class="ml-2 bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded">Revisão Obrigatória</span>
          </div>
          <p class="text-sm text-gray-400 mb-6">
            Revise cuidadosamente as alterações abaixo antes de confirmar. Esta ação afetará as permissões dos documentos selecionados.
          </p>
          
          <div v-if="previewData">
            <!-- Resumo -->
            <div class="bg-blue-50 p-4 rounded-lg mb-6">
              <h4 class="font-semibold text-blue-800 mb-2">Resumo das Alterações</h4>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                  <span class="text-gray-600">Total de documentos:</span>
                  <div class="font-semibold">{{ previewData.summary?.total_documents || 0 }}</div>
                </div>
                <div>
                  <span class="text-gray-600">Documentos alterados:</span>
                  <div class="font-semibold text-orange-600">{{ previewData.summary?.documents_with_changes || 0 }}</div>
                </div>
                <div>
                  <span class="text-gray-600">Permissões adicionadas:</span>
                  <div class="font-semibold text-green-600">{{ previewData.summary?.total_permissions_added || 0 }}</div>
                </div>
                <div>
                  <span class="text-gray-600">Permissões removidas:</span>
                  <div class="font-semibold text-red-600">{{ previewData.summary?.total_permissions_removed || 0 }}</div>
                </div>
              </div>
            </div>

            <!-- Avisos/Alertas -->
            <div v-if="previewData.warning_messages?.length" class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4 mb-6">
              <div class="flex">
                <div class="ml-3">
                  <h4 class="text-sm font-medium text-yellow-800">⚠️ Atenção</h4>
                  <div class="mt-2 text-sm text-yellow-700">
                    <ul class="list-disc list-inside space-y-1">
                      <li v-for="warning in previewData.warning_messages" :key="warning">{{ warning }}</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- Lista de documentos sem alterações -->
            <div v-if="previewData.summary?.documents_unchanged > 0" class="bg-gray-50 p-3 rounded mb-4">
              <details>
                <summary class="cursor-pointer text-gray-600 text-gray-200 text-sm">
                  {{ previewData.summary.documents_unchanged }} documento(s) não terão alterações
                </summary>
              </details>
            </div>

            <!-- Detalhes das mudanças por documento -->
            <div v-if="previewData.changes?.length" class="space-y-4">
              <div class="flex items-center justify-between">
                <h4 class="font-semibold text-gray-800 dark:text-gray-200">Documentos com Alterações:</h4>
                <Button 
                  @click="showDetailedChanges = !showDetailedChanges"
                  variant="outline"
                  class="flex items-center gap-2 text-sm"
                >
                  <span>{{ showDetailedChanges ? 'Ocultar' : 'Ver' }} Detalhes</span>
                  <ChevronDown v-if="!showDetailedChanges" class="h-4 w-4" />
                  <ChevronUp v-else class="h-4 w-4" />
                </Button>
              </div>

              <!-- Lista resumida quando não expandida -->
              <div v-if="!showDetailedChanges" class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                  Os seguintes documentos terão alterações:
                </p>
                <div class="flex flex-wrap gap-2">
                  <span 
                    v-for="change in previewData.changes.slice(0, 5)" 
                    :key="change.document_id"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                  >
                    {{ change.document_title }}
                    <span class="ml-1 bg-blue-200 dark:bg-blue-800 px-1 rounded-full">{{ change.change_count }}</span>
                  </span>
                  <span 
                    v-if="previewData.changes.length > 5"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                  >
                    +{{ previewData.changes.length - 5 }} mais...
                  </span>
                </div>
              </div>
              
              <!-- Lista detalhada quando expandida -->
              <div v-if="showDetailedChanges" class="space-y-3">
                <div v-for="change in previewData.changes" :key="change.document_id" class="border rounded-lg p-4 bg-white dark:bg-zinc-950 shadow-sm dark:shadow-gray-500">
                  <div class="flex justify-between items-start mb-3">
                    <div>
                      <h5 class="font-medium text-gray-900 dark:text-gray-200">{{ change.document_title }}</h5>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Tipo: {{ change.document_type || 'N/A' }}</p>
                    </div>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                      {{ change.change_count }} alterações
                    </span>
                  </div>

                  <!-- Estado atual vs novo estado -->
                  <div class="grid md:grid-cols-2 gap-4 mb-3">
                    <div class="bg-green-100 p-3 rounded">
                      <h6 class="text-sm font-semibold text-gray-700 mb-2">Permissões Atuais</h6>
                      <div class="text-sm space-y-1">
                        <div>
                          <span class="text-gray-600">Leitura:</span>
                          <span class="ml-1 text-green-800 font-semibold">{{ change.current_permissions?.read_groups?.join(', ') || 'Nenhuma' }}</span>
                        </div>
                        <div>
                          <span class="text-gray-600">Escrita:</span>
                          <span class="ml-1 text-green-800 font-semibold">{{ change.current_permissions?.write_groups?.join(', ') || 'Nenhuma' }}</span>
                        </div>
                      </div>
                    </div>

                    <div class="bg-blue-100 p-3 rounded">
                      <h6 class="text-sm font-semibold text-blue-700 mb-2">Novas Permissões</h6>
                      <div class="text-sm space-y-1">
                        <div>
                          <span class="text-gray-600">Leitura:</span>
                          <span class="ml-1 text-blue-800 font-semibold">{{ change.new_permissions?.read_groups?.join(', ') || 'Nenhuma' }}</span>
                        </div>
                        <div>
                          <span class="text-gray-600">Escrita:</span>
                          <span class="ml-1 text-blue-800 font-semibold">{{ change.new_permissions?.write_groups?.join(', ') || 'Nenhuma' }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Mudanças específicas -->
                  <div class="space-y-2">
                    <div v-if="change.changes?.read_groups_to_add?.length" class="flex items-center text-sm">
                      <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                      <span class="text-green-700">Leitura adicionada: {{ change.changes.read_groups_to_add.join(', ') }}</span>
                    </div>
                    <div v-if="change.changes?.read_groups_to_remove?.length" class="flex items-center text-sm">
                      <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                      <span class="text-red-700">Leitura removida: {{ change.changes.read_groups_to_remove.join(', ') }}</span>
                    </div>
                    <div v-if="change.changes?.write_groups_to_add?.length" class="flex items-center text-sm">
                      <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                      <span class="text-green-700">Escrita adicionada: {{ change.changes.write_groups_to_add.join(', ') }}</span>
                    </div>
                    <div v-if="change.changes?.write_groups_to_remove?.length" class="flex items-center text-sm">
                      <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                      <span class="text-red-700">Escrita removida: {{ change.changes.write_groups_to_remove.join(', ') }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else-if="previewData.summary?.documents_with_changes === 0" class="text-center py-8 text-gray-500">
              <p>Nenhum documento terá alterações com as permissões selecionadas.</p>
            </div>
          </div>
          
          <div class="flex justify-end gap-2 mt-6 pt-4 border-t">
            <Button @click="showPreview = false; showDetailedChanges = false" variant="outline" class="bg-zinc-600 text-white dark:bg-zinc-500">Cancelar</Button>
            <Button 
              v-if="previewData?.changes?.length > 0"
              @click="applyChanges()" 
              class="bg-green-600 hover:bg-green-700 text-white"
            >
              Confirmar Alterações ({{ previewData.summary?.documents_with_changes || 0 }} documentos)
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- Notification Toast -->
    <div
      v-if="showNotification && jobNotification"
      class="fixed top-4 right-4 z-50 max-w-md w-full bg-white border border-gray-200 rounded-lg shadow-lg transition-all duration-300 ease-in-out"
    >
      <div class="p-4">
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div v-html="jobNotification" class="text-sm text-gray-700 leading-relaxed"></div>
          </div>
          <button
            @click="closeNotification"
            class="ml-3 flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X class="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
