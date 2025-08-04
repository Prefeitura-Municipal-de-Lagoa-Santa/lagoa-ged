<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref, Transition } from 'vue';
import { BreadcrumbItem } from '@/types';
import { useMetadataTranslation } from '@/composables/useMetadataTranslation';
import { ChevronDown, ChevronUp } from 'lucide-vue-next';

const props = defineProps({
    document: {
        type: Object,
        required: true,
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
});

// Usar o composable de tradução
const { translateKey, formatMetadataValue } = useMetadataTranslation();

// Estado para controlar se o card está expandido
const isExpanded = ref(false);

// Função para alternar o estado de expansão
const toggleExpansion = () => {
    isExpanded.value = !isExpanded.value;
};

const documentSrc = computed(() => {
    if (!props.document) return '';
    return route('documents.view', props.document.id || props.document._id);
});

const isPdf = computed(() => props.document.mime_type === 'application/pdf');
const isImage = computed(() => props.document.mime_type.startsWith('image/'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pagina Inicial', href: route('dashboard') },
    { title: 'Documentos', href: route('documents.index') },
    { title: props.document.title, href: '' } // href vazio para o item atual
];

// Computed property para exibir todos os metadados
const allMetadata = computed(() => {
    return props.document.metadata || {};
});
</script>

<template>
    <Head :title="`Visualizando: ${props.document.title}`" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-stone-950/95 p-8 rounded-2xl shadow-xl mb-10 border border-gray-200 dark:border-stone-800">
                <!-- Cabeçalho do card com título e botão de expansão -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-2">
                    <div class="flex items-center gap-4">
                        <h1 class="text-3xl font-bold dark:text-white text-gray-900 mb-1">{{ props.document.title }}</h1>
                        <template v-if="props.canEdit">
                            <a :href="route('documents.edit', props.document._id || props.document.id)"
                               class="ml-2 px-4 py-2 text-base font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow transition">
                                Editar
                            </a>
                        </template>
                    </div>
                    <button 
                        @click="toggleExpansion"
                        class="expand-button flex items-center gap-2 px-5 py-2 text-base font-semibold text-gray-700 dark:text-gray-200 hover:text-blue-700 dark:hover:text-blue-400 rounded-xl hover:bg-gray-100 dark:hover:bg-stone-800 border border-gray-200 dark:border-stone-700 shadow transition-all duration-200"
                    >
                        <span>{{ isExpanded ? 'Ocultar detalhes' : 'Ver detalhes' }}</span>
                        <ChevronDown v-if="!isExpanded" class="w-5 h-5 transition-transform duration-200" />
                        <ChevronUp v-else class="w-5 h-5 transition-transform duration-200" />
                    </button>
                </div>
                
                <!-- Conteúdo expansível dos metadados -->
                <Transition
                    name="expand"
                    enter-active-class="transition-all duration-300 ease-out"
                    leave-active-class="transition-all duration-300 ease-in"
                    enter-from-class="opacity-0 transform scale-y-95"
                    enter-to-class="opacity-100 transform scale-y-100"
                    leave-from-class="opacity-100 transform scale-y-100"
                    leave-to-class="opacity-0 transform scale-y-95"
                >
                    <div v-if="isExpanded" class="pt-4 border-t border-gray-200 dark:border-stone-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-6 gap-x-8 text-base">
                            <div 
                                v-for="(value, key) in allMetadata" 
                                :key="key"
                                class="space-y-1 bg-gray-50 dark:bg-stone-900/60 rounded-xl p-4 border border-gray-100 dark:border-stone-800 shadow"
                            >
                                <div class="font-semibold text-gray-800 dark:text-gray-200 text-xs uppercase tracking-wide">
                                    {{ translateKey(String(key)) }}
                                </div>
                                <div class="text-gray-600 dark:text-gray-400 break-words">
                                    {{ formatMetadataValue(String(key), value) }}
                                </div>
                            </div>

                            <div 
                                v-if="props.document.tags && props.document.tags.length > 0"
                                class="col-span-full space-y-2 pt-2 border-t border-gray-100 dark:border-stone-800"
                            >
                                <div class="font-semibold text-gray-800 dark:text-gray-200 text-xs uppercase tracking-wide">
                                    {{ translateKey('tags') }}
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span 
                                        v-for="tag in props.document.tags" 
                                        :key="tag"
                                        class="inline-flex items-center px-4 py-1 rounded-full text-base font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 shadow"
                                    >
                                        {{ tag }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
            <div class="document-viewer border rounded-2xl overflow-hidden shadow bg-gray-50 dark:bg-stone-900/80">
                <div v-if="isPdf">
                    <embed :src="documentSrc" type="application/pdf" width="100%" height="800px" class="bg-gray-100 dark:bg-stone-900" />
                </div>

                <div v-else-if="isImage">
                    <img :src="documentSrc" :alt="props.document.title" class="max-w-full h-auto mx-auto rounded-xl shadow" />
                </div>

                <div v-else class="p-12 text-center bg-gray-100 dark:bg-stone-900/60 rounded-xl">
                    <p class="text-lg text-gray-700 dark:text-gray-300">Não é possível pré-visualizar este tipo de arquivo ({{ props.document.mime_type }}).</p>
                    <a :href="documentSrc" :download="props.document.original_name"
                        class="mt-6 inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-xl shadow hover:bg-blue-700 transition">
                        Fazer Download
                    </a>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>

.document-viewer {
    min-height: 600px;
    background: transparent;
}

/* Animação suave para o conteúdo expansível */
.metadata-content {
    overflow: hidden;
    transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
}

.metadata-content.expanded {
    max-height: 1000px; /* Altura máxima para a animação */
}

.metadata-content.collapsed {
    max-height: 0;
}

/* Hover effects para o botão */
.expand-button {
    transition: all 0.2s ease-in-out;
}

.expand-button:hover {
    transform: translateY(-1px);
}
</style>