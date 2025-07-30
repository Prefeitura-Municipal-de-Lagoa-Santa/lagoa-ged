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
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-stone-950 p-6 rounded-lg shadow-md mb-8">
                <!-- Cabeçalho do card com título e botão de expansão -->
                <div class="flex items-center justify-between mb-2">
                    <h1 class="text-2xl dark:text-white md:text-3xl font-semibold text-gray-800">
                        {{ props.document.title }}
                    </h1>
                    
                    <button 
                        @click="toggleExpansion"
                        class="expand-button flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-stone-800 transition-all duration-200 border border-gray-200 dark:border-stone-700 hover:border-gray-300 dark:hover:border-stone-600"
                    >
                        <span>{{ isExpanded ? 'Ocultar detalhes' : 'Ver detalhes' }}</span>
                        <ChevronDown v-if="!isExpanded" class="w-4 h-4 transition-transform duration-200" />
                        <ChevronUp v-else class="w-4 h-4 transition-transform duration-200" />
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
                    <div v-if="isExpanded" class="pt-3 border-t border-gray-200 dark:border-stone-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-4 gap-x-6 text-sm">
                            <div 
                                v-for="(value, key) in allMetadata" 
                                :key="key"
                                class="space-y-1"
                            >
                                <div class="font-medium text-gray-800 dark:text-gray-200 text-xs uppercase tracking-wide">
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
                                <div class="font-medium text-gray-800 dark:text-gray-200 text-xs uppercase tracking-wide">
                                    {{ translateKey('tags') }}
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span 
                                        v-for="tag in props.document.tags" 
                                        :key="tag"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400"
                                    >
                                        {{ tag }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
            <div class="document-viewer border rounded-lg overflow-hidden">
                <div v-if="isPdf">
                    <embed :src="documentSrc" type="application/pdf" width="100%" height="800px" />
                </div>

                <div v-else-if="isImage">
                    <img :src="documentSrc" :alt="props.document.title" class="max-w-full h-auto" />
                </div>

                <div v-else class="p-8 text-center bg-gray-100">
                    <p class="text-lg">Não é possível pré-visualizar este tipo de arquivo ({{ props.document.mime_type }}).</p>
                    <a :href="documentSrc" :download="props.document.original_name"
                        class="mt-4 inline-block bg-blue-500 text-white font-bold py-2 px-4 rounded hover:bg-blue-700">
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