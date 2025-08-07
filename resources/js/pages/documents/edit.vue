// ...existing code...
<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, type PropType } from 'vue';
import { useMetadataTranslation } from '@/composables/useMetadataTranslation';
import { X } from 'lucide-vue-next';
import DocumentForm from '@/components/forms/DocumentForm.vue';

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
        <div class="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-stone-950/95 p-8 rounded-2xl shadow-xl max-w-4xl mx-auto border border-gray-200 dark:border-stone-800">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold dark:text-white mb-1">Editar Documento</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-base">Atualize os dados, metadados, tags e permissões deste documento.</p>
                    </div>
                    <a :href="route('documents.show', props.document._id || props.document.id)" 
                       class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition rounded-full p-2">
                        <X class="w-6 h-6" />
                    </a>
                </div>

                <DocumentForm
                    :form="form"
                    :groups="props.groups"
                    :isEditing="true"
                    @submit="submit"
                >
                    <template #actions>
                        <a
                            :href="route('documents.show', props.document._id || props.document.id)"
                            class="px-5 py-2 rounded-xl bg-gray-200 dark:bg-stone-800 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-stone-700 font-semibold transition"
                        >
                            Cancelar
                        </a>
                        <button
                            type="submit"
                            :disabled="submitting || form.processing"
                            class="px-7 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed transition text-lg"
                        >
                            <span v-if="submitting || form.processing">Salvando...</span>
                            <span v-else>Salvar Alterações</span>
                        </button>
                    </template>
                </DocumentForm>
            </div>
        </div>
    </DashboardLayout>
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
