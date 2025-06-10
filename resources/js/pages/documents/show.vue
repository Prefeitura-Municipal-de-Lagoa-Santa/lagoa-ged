<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { BreadcrumbItem } from '@/types';

// 1. Props simplificadas - não precisamos mais do 'fileContent'
const props = defineProps({
    document: {
        type: Object,
        required: true,
    },
});

// 2. documentSrc agora aponta para a rota que serve o arquivo
const documentSrc = computed(() => {
    if (!props.document) return '';
    // Use ._id se o seu ID do MongoDB for assim
    return route('documents.view', props.document.id || props.document._id);
});

// Lógica para verificar o tipo de arquivo (permanece a mesma)
const isPdf = computed(() => props.document.mime_type === 'application/pdf');
const isImage = computed(() => props.document.mime_type.startsWith('image/'));

// 3. Breadcrumbs dinâmicos
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pagina Inicial', href: route('dashboard') },
    { title: 'Documentos', href: route('documents.index') },
    { title: props.document.title }
];

</script>

<template>

    <Head :title="`Visualizando: ${props.document.title}`" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h1 class="text-2xl md:text-3xl font-semibold text-foreground">
                    {{ props.document.title }}
                </h1>
            </div>

            <div class="document-viewer border rounded-lg overflow-hidden">
                <div v-if="isPdf">
                    <embed :src="documentSrc" type="application/pdf" width="100%" height="800px" />
                </div>

                <div v-else-if="isImage">
                    <img :src="documentSrc" :alt="props.document.title" class="max-w-full h-auto" />
                </div>

                <div v-else class="p-8 text-center bg-gray-100">
                    <p class="text-lg">Não é possível pré-visualizar este tipo de arquivo ({{ props.document.mime_type }}).
                    </p>
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
</style>