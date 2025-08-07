<script setup lang="ts">
import { ref, computed, watch, type PropType } from 'vue';
import { X, Plus, Users } from 'lucide-vue-next';
import GroupManagerModal from '@/components/modals/GroupManagerModal.vue';
import { useMetadataTranslation } from '@/composables/useMetadataTranslation';

// Interfaces
interface Group {
    id: string;
    name: string;
}

interface DocumentPermissions {
    read_group_ids: string[];
    write_group_ids: string[];
}

interface DocumentFormProps {
    form: any; // useForm do Inertia
    groups: Group[];
    isEditing: boolean;
    documentTitle?: string;
}

const props = defineProps<DocumentFormProps>();
const emit = defineEmits(['submit']);

const { translateKey } = useMetadataTranslation();

// Normalizar grupos
const normalizedGroups = computed(() => props.groups);

// Metadados
const newMetaKey = ref('');
const newMetaValue = ref('');
function addMetadataField() {
    if (newMetaKey.value && !(newMetaKey.value in props.form.metadata)) {
        props.form.metadata[newMetaKey.value] = newMetaValue.value;
        newMetaKey.value = '';
        newMetaValue.value = '';
    }
}
function removeMetadataField(key: string) {
    delete props.form.metadata[key];
}
const sortedMetadata = computed(() => {
    return Object.entries(props.form.metadata).sort(([a], [b]) => a.localeCompare(b));
});

// Tags
const newTag = ref('');
function addTag() {
    const tag = newTag.value.trim().toUpperCase();
    if (tag && !props.form.tags.includes(tag)) {
        props.form.tags.push(tag);
        newTag.value = '';
    }
}
function removeTag(tag: string) {
    props.form.tags = props.form.tags.filter((t: string) => t !== tag);
}

// Permissões
const showReadGroupsModal = ref(false);
const showWriteGroupsModal = ref(false);
const readGroups = computed(() => {
    return props.groups.filter(group => props.form.permissions.read_group_ids.includes(group.id));
});
const writeGroups = computed(() => {
    return props.groups.filter(group => props.form.permissions.write_group_ids.includes(group.id));
});
function confirmReadGroups(selectedGroups: Group[]) {
    props.form.permissions.read_group_ids = selectedGroups.map(g => g.id);
}
function confirmWriteGroups(selectedGroups: Group[]) {
    props.form.permissions.write_group_ids = selectedGroups.map(g => g.id);
}

function submitForm() {
    emit('submit');
}
</script>

<template>
    <form @submit.prevent="submitForm" class="space-y-10">
        <!-- Título -->
        <div>
            <label class="block text-base font-semibold text-gray-700 dark:text-gray-200 mb-2">
                Título do Documento
            </label>
            <input 
                v-model="props.form.title" 
                type="text" 
                class="w-full rounded-xl border-gray-300 dark:bg-stone-800 dark:border-stone-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-4 py-2 text-lg" 
                required 
            />
            <div v-if="props.form.errors.title" class="text-red-500 text-sm mt-1">{{ props.form.errors.title }}</div>
        </div>

        <!-- Metadados Dinâmicos -->
        <div>
            <label class="block text-base font-semibold text-gray-700 dark:text-gray-200 mb-3">
                Metadados do Documento
            </label>
            <div class="space-y-3">
                <div 
                    v-for="[key, value] in sortedMetadata" 
                    :key="key" 
                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-stone-900/80 rounded-xl border border-gray-200 dark:border-stone-800"
                >
                    <div class="flex-1 grid grid-cols-2 gap-3">
                        <div class="text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center">
                            {{ translateKey(key) }}
                            <span class="ml-2 text-xs text-gray-400">({{ key }})</span>
                        </div>
                        <input 
                            v-model="props.form.metadata[key]" 
                            type="text" 
                            class="w-full rounded-lg border-gray-300 dark:bg-stone-800 dark:border-stone-700 dark:text-white text-base px-3 py-1"
                            :placeholder="`Valor para ${key}`"
                        />
                    </div>
                    <button 
                        type="button" 
                        @click="removeMetadataField(key)" 
                        class="text-red-500 hover:text-red-700 p-1 rounded-full"
                        title="Remover campo"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Adicionar novo metadado -->
                <div class="flex gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                    <input 
                        v-model="newMetaKey" 
                        type="text" 
                        placeholder="Nome do campo (ex: numero_processo)" 
                        class="flex-1 rounded-lg border-gray-300 dark:bg-stone-800 dark:border-stone-700 dark:text-white text-base px-3 py-1"
                    />
                    <input 
                        v-model="newMetaValue" 
                        type="text" 
                        placeholder="Valor do campo" 
                        class="flex-1 rounded-lg border-gray-300 dark:bg-stone-800 dark:border-stone-700 dark:text-white text-base px-3 py-1"
                    />
                    <button 
                        type="button" 
                        @click="addMetadataField" 
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-base font-semibold flex items-center gap-2 transition"
                    >
                        <Plus class="w-4 h-4" />
                        Adicionar
                    </button>
                </div>
            </div>
        </div>

        <!-- Tags -->
        <div>
            <label class="block text-base font-semibold text-gray-700 dark:text-gray-200 mb-3">
                Tags do Documento
            </label>
            <div class="space-y-3">
                <!-- Tags existentes -->
                <div class="flex flex-wrap gap-2">
                    <span 
                        v-for="tag in props.form.tags" 
                        :key="tag" 
                        class="inline-flex items-center px-4 py-1 rounded-full text-base font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 shadow"
                    >
                        {{ tag }}
                        <button 
                            type="button" 
                            @click="removeTag(tag)" 
                            class="ml-2 text-red-500 hover:text-red-700 rounded-full"
                            title="Remover tag"
                        >
                            <X class="w-3 h-3" />
                        </button>
                    </span>
                    <span v-if="props.form.tags.length === 0" class="text-gray-500 dark:text-gray-400 text-sm italic">
                        Nenhuma tag adicionada
                    </span>
                </div>

                <!-- Adicionar nova tag -->
                <div class="flex gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                    <input 
                        v-model="newTag" 
                        type="text" 
                        placeholder="Digite a nova tag (ex: ADLP, MUNICIPAL)" 
                        class="flex-1 rounded-lg border-gray-300 dark:bg-stone-800 dark:border-stone-700 dark:text-white text-base px-3 py-1"
                        @keyup.enter="addTag"
                    />
                    <button 
                        type="button" 
                        @click="addTag" 
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-base font-semibold flex items-center gap-2 transition"
                    >
                        <Plus class="w-4 h-4" />
                        Adicionar
                    </button>
                </div>
            </div>
        </div>

        <!-- Permissões -->
        <div class="space-y-6">
            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-200 mb-2">Permissões de Acesso</h3>
            <!-- Grupos que podem ler -->
            <div>
                <label class="block text-base font-semibold text-gray-700 dark:text-gray-200 mb-2">
                    Grupos que podem visualizar
                </label>
                <div class="border border-gray-300 dark:border-stone-800 rounded-xl p-4 bg-gray-50 dark:bg-stone-900/80">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span v-if="readGroups.length === 0" class="text-gray-500 dark:text-gray-400 text-sm italic">
                            Nenhum grupo selecionado
                        </span>
                        <span 
                            v-for="group in readGroups" 
                            :key="group.id" 
                            class="inline-flex items-center px-4 py-1 rounded-full text-base font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 shadow"
                        >
                            {{ group.name }}
                        </span>
                    </div>
                    <button 
                        type="button"
                        @click="showReadGroupsModal = true"
                        class="flex items-center gap-2 px-4 py-2 text-base rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-semibold transition"
                    >
                        <Users class="w-4 h-4" />
                        Gerenciar Grupos
                    </button>
                </div>
            </div>
            <!-- Grupos que podem editar -->
            <div>
                <label class="block text-base font-semibold text-gray-700 dark:text-gray-200 mb-2">
                    Grupos que podem editar
                </label>
                <div class="border border-gray-300 dark:border-stone-800 rounded-xl p-4 bg-gray-50 dark:bg-stone-900/80">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span v-if="writeGroups.length === 0" class="text-gray-500 dark:text-gray-400 text-sm italic">
                            Nenhum grupo selecionado
                        </span>
                        <span 
                            v-for="group in writeGroups" 
                            :key="group.id" 
                            class="inline-flex items-center px-4 py-1 rounded-full text-base font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 shadow"
                        >
                            {{ group.name }}
                        </span>
                    </div>
                    <button 
                        type="button"
                        @click="showWriteGroupsModal = true"
                        class="flex items-center gap-2 px-4 py-2 text-base rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-semibold transition"
                    >
                        <Users class="w-4 h-4" />
                        Gerenciar Grupos
                    </button>
                </div>
            </div>
        </div>

        <!-- Botões de ação -->
        <div class="flex justify-end gap-4 pt-8 border-t border-gray-200 dark:border-stone-800">
            <slot name="actions"></slot>
        </div>
    </form>

    <!-- Modal de grupos para leitura -->
    <GroupManagerModal
        v-model="showReadGroupsModal"
        :all-groups="normalizedGroups"
        :initial-selected-groups="readGroups"
        username="Usuário"
        @confirm="confirmReadGroups"
    />
    <!-- Modal de grupos para escrita -->
    <GroupManagerModal
        v-model="showWriteGroupsModal"
        :all-groups="normalizedGroups"
        :initial-selected-groups="writeGroups"
        username="Usuário"
        @confirm="confirmWriteGroups"
    />
</template>

<style scoped>
select[multiple] option:checked {
    background: #3b82f6;
    color: white;
}
select[multiple] option {
    padding: 0.25rem 0.5rem;
}
</style>
