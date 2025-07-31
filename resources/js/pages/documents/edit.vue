// LOG DE DEPURAÇÃO INICIAL
console.log('props.groups:', JSON.stringify(props.groups));
console.log('form.permissions.read_group_ids:', JSON.stringify(form.permissions.read_group_ids));
console.log('form.permissions.write_group_ids:', JSON.stringify(form.permissions.write_group_ids));
console.log('readGroups inicial:', JSON.stringify(readGroups.value));
console.log('writeGroups inicial:', JSON.stringify(writeGroups.value));
<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, type PropType } from 'vue';
import { useMetadataTranslation } from '@/composables/useMetadataTranslation';
import { X, Plus, Users } from 'lucide-vue-next';
import GroupManagerModal from '@/components/modals/GroupManagerModal.vue';

const props = defineProps({
    document: {
        type: Object,
        required: true,
    },
    groups: {
        type: Array as PropType<Array<{ id: string; name: string }>>,
        required: true,
    },
});

const { translateKey } = useMetadataTranslation();
const page = usePage();

// Grupos já vêm no formato correto
const normalizedGroups = computed(() => props.groups);

function getGroupById(id: string) {
    return props.groups.find(g => g.id === id);
}

// Obter o nome do usuário atual
const currentUser = computed(() => {
    const auth = page.props.auth as any;
    return auth?.user || {};
});

// Inicialização dinâmica dos metadados
const initialMetadata = { ...props.document.metadata };

function normalizeId(id: any): string {
    if (typeof id === 'string') return id;
    if (id && typeof id === 'object' && ('$oid' in id)) return id.$oid;
    return String(id);
}

function normalizeIdArray(arr: any[]): string[] {
    return (arr || []).map(normalizeId);
}

const form = useForm({
    title: props.document.title || '',
    metadata: { ...initialMetadata },
    tags: [...(props.document.tags || [])],
    permissions: {
        read_group_ids: normalizeIdArray(props.document.permissions?.read_group_ids),
        write_group_ids: normalizeIdArray(props.document.permissions?.write_group_ids),
    },
});

// Para adicionar/remover campos dinâmicos de metadados
const newMetaKey = ref('');
const newMetaValue = ref('');

function addMetadataField() {
    if (newMetaKey.value && !(newMetaKey.value in form.metadata)) {
        form.metadata[newMetaKey.value] = newMetaValue.value;
        newMetaKey.value = '';
        newMetaValue.value = '';
    }
}

function removeMetadataField(key: string) {
    delete form.metadata[key];
}

// Para adicionar/remover tags
const newTag = ref('');

function addTag() {
    const tag = newTag.value.trim().toUpperCase();
    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag);
        newTag.value = '';
    }
}

function removeTag(tag: string) {
    form.tags = form.tags.filter(t => t !== tag);
}

// Computed para metadados ordenados
const sortedMetadata = computed(() => {
    return Object.entries(form.metadata).sort(([a], [b]) => a.localeCompare(b));
});

// Para os modais de gerenciamento de grupos
const showReadGroupsModal = ref(false);
const showWriteGroupsModal = ref(false);

// Depuração: logar sempre que abrir o modal
watch(showReadGroupsModal, (val) => {
    if (val) {
        console.log('Abrindo modal de leitura. form.permissions.read_group_ids:', JSON.stringify(form.permissions.read_group_ids));
        console.log('readGroups:', JSON.stringify(readGroups.value));
    }
});
watch(showWriteGroupsModal, (val) => {
    if (val) {
        console.log('Abrindo modal de escrita. form.permissions.write_group_ids:', JSON.stringify(form.permissions.write_group_ids));
        console.log('writeGroups:', JSON.stringify(writeGroups.value));
    }
});

// Computed para grupos selecionados nas permissões (convertidos para o formato do modal)
const readGroups = computed(() => {
    return props.groups.filter(group => form.permissions.read_group_ids.includes(group.id));
});

const writeGroups = computed(() => {
    return props.groups.filter(group => form.permissions.write_group_ids.includes(group.id));
});

// Handlers para os modais de grupos
function confirmReadGroups(selectedGroups: any[]) {
    console.log('Confirmando leitura. Selecionados:', JSON.stringify(selectedGroups));
    form.permissions.read_group_ids = selectedGroups.map(g => g.id);
    console.log('Após confirmar leitura, form.permissions.read_group_ids:', JSON.stringify(form.permissions.read_group_ids));
}

function confirmWriteGroups(selectedGroups: any[]) {
    console.log('Confirmando escrita. Selecionados:', JSON.stringify(selectedGroups));
    form.permissions.write_group_ids = selectedGroups.map(g => g.id);
    console.log('Após confirmar escrita, form.permissions.write_group_ids:', JSON.stringify(form.permissions.write_group_ids));
}

const submitting = ref(false);

function submit() {
    submitting.value = true;
    form.put(route('documents.update', props.document._id || props.document.id), {
        onFinish: () => { submitting.value = false; },
    });
}
</script>

<template>
    <Head :title="`Editar: ${props.document.title}`" />
    
    <DashboardLayout>
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-stone-950 p-6 rounded-lg shadow-md max-w-4xl mx-auto">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold dark:text-white">Editar Documento</h1>
                    <a :href="route('documents.show', props.document._id || props.document.id)" 
                       class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <X class="w-6 h-6" />
                    </a>
                </div>

                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Título -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Título do Documento
                        </label>
                        <input 
                            v-model="form.title" 
                            type="text" 
                            class="w-full rounded-lg border-gray-300 dark:bg-stone-800 dark:border-stone-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                            required 
                        />
                        <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</div>
                    </div>

                    <!-- Metadados Dinâmicos -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Metadados do Documento
                        </label>
                        <div class="space-y-3">
                            <div 
                                v-for="[key, value] in sortedMetadata" 
                                :key="key" 
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-stone-800 rounded-lg"
                            >
                                <div class="flex-1 grid grid-cols-2 gap-3">
                                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center">
                                        {{ translateKey(key) }}
                                        <span class="ml-2 text-xs text-gray-400">({{ key }})</span>
                                    </div>
                                    <input 
                                        v-model="form.metadata[key]" 
                                        type="text" 
                                        class="w-full rounded border-gray-300 dark:bg-stone-700 dark:border-stone-600 dark:text-white text-sm"
                                        :placeholder="`Valor para ${key}`"
                                    />
                                </div>
                                <button 
                                    type="button" 
                                    @click="removeMetadataField(key)" 
                                    class="text-red-500 hover:text-red-700 p-1"
                                    title="Remover campo"
                                >
                                    <X class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Adicionar novo metadado -->
                            <div class="flex gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <input 
                                    v-model="newMetaKey" 
                                    type="text" 
                                    placeholder="Nome do campo (ex: numero_processo)" 
                                    class="flex-1 rounded border-gray-300 dark:bg-stone-700 dark:border-stone-600 dark:text-white text-sm"
                                />
                                <input 
                                    v-model="newMetaValue" 
                                    type="text" 
                                    placeholder="Valor do campo" 
                                    class="flex-1 rounded border-gray-300 dark:bg-stone-700 dark:border-stone-600 dark:text-white text-sm"
                                />
                                <button 
                                    type="button" 
                                    @click="addMetadataField" 
                                    class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700 text-sm flex items-center gap-1"
                                >
                                    <Plus class="w-4 h-4" />
                                    Adicionar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Tags do Documento
                        </label>
                        <div class="space-y-3">
                            <!-- Tags existentes -->
                            <div class="flex flex-wrap gap-2">
                                <span 
                                    v-for="tag in form.tags" 
                                    :key="tag" 
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400"
                                >
                                    {{ tag }}
                                    <button 
                                        type="button" 
                                        @click="removeTag(tag)" 
                                        class="ml-2 text-red-500 hover:text-red-700"
                                        title="Remover tag"
                                    >
                                        <X class="w-3 h-3" />
                                    </button>
                                </span>
                                <span v-if="form.tags.length === 0" class="text-gray-500 dark:text-gray-400 text-sm italic">
                                    Nenhuma tag adicionada
                                </span>
                            </div>

                            <!-- Adicionar nova tag -->
                            <div class="flex gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                <input 
                                    v-model="newTag" 
                                    type="text" 
                                    placeholder="Digite a nova tag (ex: ADLP, MUNICIPAL)" 
                                    class="flex-1 rounded border-gray-300 dark:bg-stone-700 dark:border-stone-600 dark:text-white text-sm"
                                    @keyup.enter="addTag"
                                />
                                <button 
                                    type="button" 
                                    @click="addTag" 
                                    class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 text-sm flex items-center gap-1"
                                >
                                    <Plus class="w-4 h-4" />
                                    Adicionar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Permissões -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">Permissões de Acesso</h3>
                        
                        <!-- Grupos que podem ler -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Grupos que podem visualizar
                            </label>
                            <div class="border border-gray-300 dark:border-stone-600 rounded-lg p-3 bg-gray-50 dark:bg-stone-800">
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <span v-if="readGroups.length === 0" class="text-gray-500 dark:text-gray-400 text-sm italic">
                                        Nenhum grupo selecionado
                                    </span>
                                    <span 
                                        v-for="group in readGroups" 
                                        :key="group.id" 
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"
                                    >
                                        {{ group.name }}
                                    </span>
                                </div>
                                <button 
                                    type="button"
                                    @click="showReadGroupsModal = true"
                                    class="flex items-center gap-2 px-3 py-1.5 text-sm rounded bg-blue-600 text-white hover:bg-blue-700 transition"
                                >
                                    <Users class="w-4 h-4" />
                                    Gerenciar Grupos
                                </button>
                            </div>
                        </div>

                        <!-- Grupos que podem editar -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Grupos que podem editar
                            </label>
                            <div class="border border-gray-300 dark:border-stone-600 rounded-lg p-3 bg-gray-50 dark:bg-stone-800">
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <span v-if="writeGroups.length === 0" class="text-gray-500 dark:text-gray-400 text-sm italic">
                                        Nenhum grupo selecionado
                                    </span>
                                    <span 
                                        v-for="group in writeGroups" 
                                        :key="group.id" 
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400"
                                    >
                                        {{ group.name }}
                                    </span>
                                </div>
                                <button 
                                    type="button"
                                    @click="showWriteGroupsModal = true"
                                    class="flex items-center gap-2 px-3 py-1.5 text-sm rounded bg-blue-600 text-white hover:bg-blue-700 transition"
                                >
                                    <Users class="w-4 h-4" />
                                    Gerenciar Grupos
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Botões de ação -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-stone-700">
                        <a 
                            :href="route('documents.show', props.document._id || props.document.id)" 
                            class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-stone-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-stone-600 transition"
                        >
                            Cancelar
                        </a>
                        <button 
                            type="submit" 
                            :disabled="submitting || form.processing" 
                            class="px-6 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed transition"
                        >
                            <span v-if="submitting || form.processing">Salvando...</span>
                            <span v-else>Salvar Alterações</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>

    <!-- Modal de grupos para leitura -->
    <GroupManagerModal
        v-model="showReadGroupsModal"
        :all-groups="normalizedGroups"
        :initial-selected-groups="readGroups"
        :username="currentUser.name || 'Usuário'"
        @confirm="confirmReadGroups"
    />

    <!-- Modal de grupos para escrita -->
    <GroupManagerModal
        v-model="showWriteGroupsModal"
        :all-groups="normalizedGroups"
        :initial-selected-groups="writeGroups"
        :username="currentUser.name || 'Usuário'"
        @confirm="confirmWriteGroups"
    />
</template>

<style scoped>
/* Custom styles for multi-select */
select[multiple] option:checked {
    background: #3b82f6;
    color: white;
}

select[multiple] option {
    padding: 0.25rem 0.5rem;
}
</style>
