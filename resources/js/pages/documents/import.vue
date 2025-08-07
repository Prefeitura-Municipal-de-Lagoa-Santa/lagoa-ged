<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, Head, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/ui/label/Label.vue'; // Confirme o caminho
import PrimaryButton from '@/components/ui/button/Button.vue'; // Confirme o caminho
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button'; // Importe o Button do seu UI kit
import { Label } from '@/components/ui/label'; // Importe o Label do seu UI kit

// Importe o seu GroupManagerModal
import GroupManagerModal from '@/components/modals/GroupManagerModal.vue'; // CONFIRME ESTE CAMINHO

import { BreadcrumbItem } from '@/types';

// Interfaces (copie do GroupManagerModal para garantir consistência)
interface Group {
    id: string;
    name: string;
};

const props = defineProps({
    groups: {
        type: Array as () => Group[], // Agora tipamos como Group[]
        required: true,
    },
});

const importErrors = computed(() => page.props.flash.importErrors ?? []);

const form = useForm({
    csv_file: null,
    read_group_ids: [],
    write_group_ids: [],
});

const page = usePage();

const submit = () => {
    form.post(route('documents.import.process'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset('csv_file');
            // Resetar IDs de grupo após o sucesso da importação
            form.read_group_ids = [];
            form.write_group_ids = [];
        },
        onError: (errors) => {
            console.error(errors);
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Página Inicial', href: route('dashboard') },
    { title: 'Documentos', href: route('documents.index') },
    { title: 'Importar Documentos' }
];

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.csv_file = target.files[0];
    }
};

// --- NOVA LÓGICA PARA MODAIS DE SELEÇÃO DE GRUPO ---
// Estados para controlar a abertura/fechamento de cada modal
const isReadModalOpen = ref(false);
const isWriteModalOpen = ref(false);

// Username para passar para a modal (pode ser o nome do usuário logado, ou um genérico "Documentos")
const currentUsername = computed(() => page.props.auth?.user?.name || 'Documentos');

// Funções para obter os objetos de grupo completos a partir dos IDs
// Isso é necessário porque o GroupManagerModal espera Group[], não apenas string[]
const getGroupsByIds = (ids: string[]): Group[] => {
    return props.groups.filter(group => ids.includes(group.id));
};

// Computed properties para passar para `initialSelectedGroups` da modal
const initialReadGroups = computed(() => getGroupsByIds(form.read_group_ids));
const initialWriteGroups = computed(() => getGroupsByIds(form.write_group_ids));

// Handlers para o evento 'confirm' das modais
// Recebe a lista de grupos selecionados da modal e atualiza o array de IDs do formulário
const handleReadGroupsConfirmed = (selected: Group[]) => {
    form.read_group_ids = selected.map(group => group.id);
};

const handleWriteGroupsConfirmed = (selected: Group[]) => {
    form.write_group_ids = selected.map(group => group.id);
};


// Computed properties para exibir os nomes dos grupos no formulário principal
const selectedReadGroupNames = computed(() => initialReadGroups.value.map(g => g.name));
const selectedWriteGroupNames = computed(() => initialWriteGroups.value.map(g => g.name));

</script>

<template>


    <Head :title="`Importar Documentos`" />
    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-8 px-4">
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                    Importar Documentos
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-lg">
                    Faça upload de um arquivo CSV para importar documentos em lote, atribuindo permissões de leitura e escrita.
                </p>
            </div>

            <div v-if="importErrors.length > 0"
                class="mb-8 bg-gradient-to-r from-yellow-400 to-yellow-500 text-yellow-900 p-6 rounded-2xl shadow-lg backdrop-blur-sm max-w-xl mx-auto border border-yellow-600">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-yellow-600/20 rounded-full flex items-center justify-center">
                        <span class="text-yellow-900 font-bold">!</span>
                    </div>
                    <h3 class="font-semibold text-xl">Atenção</h3>
                </div>
                <p class="text-yellow-900/90 mb-1">Alguns itens foram ignorados durante a importação:</p>
                <ul class="mt-2 list-disc list-inside">
                    <li v-for="(error, index) in importErrors" :key="index">{{ error }}</li>
                </ul>
            </div>

            <form @submit.prevent="submit" class="max-w-2xl mx-auto">
                <div class="bg-white dark:bg-stone-950/95 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-stone-800">
                    <div class="mb-6">
                        <InputLabel for="csv_file" value="Arquivo CSV" class="text-lg font-semibold" />
                        <input id="csv_file" type="file" @change="handleFileUpload"
                            class="mt-2 block w-full rounded-xl border-2 border-indigo-300 dark:border-indigo-700 bg-indigo-50 dark:bg-stone-950/95 px-4 py-3 text-base text-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-base file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700"
                            accept=".csv" />
                        <InputError :message="form.errors.csv_file" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <Label class="text-lg font-semibold">Grupos de Leitura</Label>
                            <Button type="button" variant="outline" size="sm" @click="isReadModalOpen = true" class="transition-all duration-200 bg-indigo-600 hover:bg-indigo-700 text-white">
                                Editar Grupos
                            </Button>
                        </div>
                        <div v-if="selectedReadGroupNames.length > 0"
                            class="flex flex-wrap gap-2 rounded-xl border-2 border-indigo-300 dark:border-indigo-700 bg-indigo-50 dark:bg-stone-900/80 p-3 min-h-[40px] items-center">
                            <span v-for="groupName in selectedReadGroupNames" :key="groupName"
                                class="inline-flex items-center rounded-full bg-indigo-600 px-3 py-1 text-base font-semibold shadow-sm text-white dark:bg-indigo-700">
                                {{ groupName }}
                            </span>
                        </div>
                        <div v-else
                            class="flex items-center justify-center rounded-xl border-2 border-indigo-300 dark:border-indigo-700 bg-indigo-50 dark:bg-stone-900/80 p-3 min-h-[40px] text-base text-muted-foreground dark:text-gray-400">
                            <span>Nenhum grupo de leitura selecionado.</span>
                        </div>
                        <InputError :message="form.errors.read_group_ids" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <Label class="text-lg font-semibold">Grupos de Escrita</Label>
                            <Button type="button" variant="outline" size="sm" @click="isWriteModalOpen = true" class="transition-all duration-200 bg-indigo-600 hover:bg-indigo-700 text-white">
                                Editar Grupos
                            </Button>
                        </div>
                        <div v-if="selectedWriteGroupNames.length > 0"
                            class="flex flex-wrap gap-2 rounded-xl border-2 border-indigo-300 dark:border-indigo-700 bg-indigo-50 dark:bg-stone-900/80 p-3 min-h-[40px] items-center">
                            <span v-for="groupName in selectedWriteGroupNames" :key="groupName"
                                class="inline-flex items-center rounded-full bg-indigo-600 px-3 py-1 text-base font-semibold shadow-sm text-white dark:bg-indigo-700">
                                {{ groupName }}
                            </span>
                        </div>
                        <div v-else
                            class="flex items-center justify-center rounded-xl border-2 border-indigo-300 dark:border-indigo-700 bg-indigo-50 dark:bg-stone-900/80 p-3 min-h-[40px] text-base text-muted-foreground dark:text-gray-400">
                            <span>Nenhum grupo de escrita selecionado.</span>
                        </div>
                        <InputError :message="form.errors.write_group_ids" class="mt-2" />
                    </div>

                    <div class="flex justify-end mt-8">
                        <PrimaryButton :disabled="form.processing" class="px-8 py-3 text-lg rounded-xl shadow-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition-all duration-200">
                            {{ form.processing ? 'Salvando...' : 'Importar Documentos' }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>

        <GroupManagerModal v-model="isReadModalOpen" :all-groups="props.groups" :initial-selected-groups="initialReadGroups"
            @confirm="handleReadGroupsConfirmed" :username="currentUsername" />

        <GroupManagerModal v-model="isWriteModalOpen" :all-groups="props.groups"
            :initial-selected-groups="initialWriteGroups" @confirm="handleWriteGroupsConfirmed"
            :username="currentUsername" />
    </DashboardLayout>
</template>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

.group:hover {
    animation: float 3s ease-in-out infinite;
}

.backdrop-blur-sm {
    backdrop-filter: blur(8px);
}

/* Modern card shadow and border for consistency 
.rounded-2xl {
    border-radius: 1rem;
}
.shadow-xl {
    box-shadow: 0 8px 32px 0 rgba(60, 60, 120, 0.15);
}
.border-indigo-300 {
    border-color: #a5b4fc;
}
.dark .border-indigo-700 {
    border-color: #4338ca;
}
.bg-indigo-50 {
    background-color: #eef2ff;
}
.dark .bg-zinc-800 {
    background-color: #18181b;
}
.dark .text-gray-400 {
    color: #a0aec0;
}
.dark .bg-indigo-700 {
    background-color: #4338ca;
}
.dark .bg-zinc-900 {
    background-color: #18181b;
}
.dark .border-zinc-700 {
    border-color: #3f3f46;
}
.dark .text-white {
    color: #fff;
}*/
</style>