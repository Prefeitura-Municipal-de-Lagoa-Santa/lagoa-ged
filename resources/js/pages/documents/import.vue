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
    deny_group_ids: [],
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
            form.deny_group_ids = [];
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
const isDenyModalOpen = ref(false);

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
const initialDenyGroups = computed(() => getGroupsByIds(form.deny_group_ids));

// Handlers para o evento 'confirm' das modais
// Recebe a lista de grupos selecionados da modal e atualiza o array de IDs do formulário
const handleReadGroupsConfirmed = (selected: Group[]) => {
    form.read_group_ids = selected.map(group => group.id);
};

const handleWriteGroupsConfirmed = (selected: Group[]) => {
    form.write_group_ids = selected.map(group => group.id);
};

const handleDenyGroupsConfirmed = (selected: Group[]) => {
    form.deny_group_ids = selected.map(group => group.id);
};

// Computed properties para exibir os nomes dos grupos no formulário principal
const selectedReadGroupNames = computed(() => initialReadGroups.value.map(g => g.name));
const selectedWriteGroupNames = computed(() => initialWriteGroups.value.map(g => g.name));
const selectedDenyGroupNames = computed(() => initialDenyGroups.value.map(g => g.name));

</script>

<template>

    <Head :title="`Importar Documentos`" />

    <DashboardLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h1 class="text-2xl md:text-3xl font-semibold text-foreground">
                    Importar Documentos via CSV
                </h1>
            </div>

            <div v-if="page.props.flash.success"
                class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                role="alert">
                <span class="block sm:inline">{{ page.props.flash.success }}</span>
            </div>
            <div v-if="page.props.flash.error"
                class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ page.props.flash.error }}</span>
            </div>
            
            <div v-if="importErrors.length > 0"
                class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4 max-h-[100px] overflow-y-auto"
                role="alert">
                <p class="font-bold">Atenção! Alguns itens foram ignorados durante a importação:</p>
                <ul class="mt-2 list-disc list-inside">
                    <li v-for="(error, index) in importErrors" :key="index">{{ error }}</li>
                </ul>
            </div>

            <div>
                <form @submit.prevent="submit">
                    <Card>
                        <CardContent>
                            <div class="mb-4">
                                <InputLabel for="csv_file" value="Arquivo CSV" />
                                <input id="csv_file" type="file" @change="handleFileUpload" class="mt-1 block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-violet-50 file:text-violet-700
                                    hover:file:bg-violet-100" accept=".csv" />
                                <InputError :message="form.errors.csv_file" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <Label>Grupos de Leitura</Label>
                                    <Button type="button" variant="outline" size="sm" @click="isReadModalOpen = true">
                                        Editar Grupos
                                    </Button>
                                </div>
                                <div v-if="selectedReadGroupNames.length > 0"
                                    class="flex flex-wrap gap-2 rounded-lg border bg-muted/50 p-3 min-h-[40px] items-center dark:bg-gray-900 dark:border-gray-700">
                                    <span v-for="groupName in selectedReadGroupNames" :key="groupName"
                                        class="inline-flex items-center rounded-full bg-indigo-600 px-3 py-1 text-sm font-semibold shadow-sm text-white dark:bg-indigo-700">
                                        {{ groupName }}
                                    </span>
                                </div>
                                <div v-else
                                    class="flex items-center justify-center rounded-lg border p-3 min-h-[40px] text-sm text-muted-foreground dark:bg-gray-900 dark:border-gray-700 dark:text-gray-400">
                                    <span>Nenhum grupo de leitura selecionado.</span>
                                </div>
                                <InputError :message="form.errors.read_group_ids" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <Label>Grupos de Escrita</Label>
                                    <Button type="button" variant="outline" size="sm" @click="isWriteModalOpen = true">
                                        Editar Grupos
                                    </Button>
                                </div>
                                <div v-if="selectedWriteGroupNames.length > 0"
                                    class="flex flex-wrap gap-2 rounded-lg border bg-muted/50 p-3 min-h-[40px] items-center dark:bg-gray-900 dark:border-gray-700">
                                    <span v-for="groupName in selectedWriteGroupNames" :key="groupName"
                                        class="inline-flex items-center rounded-full bg-indigo-600 px-3 py-1 text-sm font-semibold shadow-sm text-white dark:bg-indigo-700">
                                        {{ groupName }}
                                    </span>
                                </div>
                                <div v-else
                                    class="flex items-center justify-center rounded-lg border p-3 min-h-[40px] text-sm text-muted-foreground dark:bg-gray-900 dark:border-gray-700 dark:text-gray-400">
                                    <span>Nenhum grupo de escrita selecionado.</span>
                                </div>
                                <InputError :message="form.errors.write_group_ids" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <Label>Grupos Bloqueados</Label>
                                    <Button type="button" variant="outline" size="sm" @click="isDenyModalOpen = true">
                                        Editar Grupos
                                    </Button>
                                </div>
                                <div v-if="selectedDenyGroupNames.length > 0"
                                    class="flex flex-wrap gap-2 rounded-lg border bg-muted/50 p-3 min-h-[40px] items-center dark:bg-gray-900 dark:border-gray-700">
                                    <span v-for="groupName in selectedDenyGroupNames" :key="groupName"
                                        class="inline-flex items-center rounded-full bg-indigo-600 px-3 py-1 text-sm font-semibold shadow-sm text-white dark:bg-indigo-700">
                                        {{ groupName }}
                                    </span>
                                </div>
                                <div v-else
                                    class="flex items-center justify-center rounded-lg border p-3 min-h-[40px] text-sm text-muted-foreground dark:bg-gray-900 dark:border-gray-700 dark:text-gray-400">
                                    <span>Nenhum grupo bloqueado selecionado.</span>
                                </div>
                                <InputError :message="form.errors.deny_group_ids" class="mt-2" />
                            </div>

                        </CardContent>
                        <CardFooter class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Salvando...' : 'Importar Documentos' }}
                            </PrimaryButton>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </div>
    </DashboardLayout>

    <GroupManagerModal v-model="isReadModalOpen" :all-groups="props.groups" :initial-selected-groups="initialReadGroups"
        @confirm="handleReadGroupsConfirmed" :username="currentUsername" />

    <GroupManagerModal v-model="isWriteModalOpen" :all-groups="props.groups"
        :initial-selected-groups="initialWriteGroups" @confirm="handleWriteGroupsConfirmed"
        :username="currentUsername" />

    <GroupManagerModal v-model="isDenyModalOpen" :all-groups="props.groups" :initial-selected-groups="initialDenyGroups"
        @confirm="handleDenyGroupsConfirmed" :username="currentUsername" />
</template>

<style scoped>
/* Adapte estas classes para seu tema escuro, se não estiverem no seu tailwind.config.js */
.dark\:bg-muted\/50 {
    background-color: rgba(30, 30, 30, 0.5);
    /* Um tom mais escuro para bg-muted/50 no dark mode */
}

.dark\:border-gray-700 {
    border-color: #4A5568;
}

.dark\:text-gray-400 {
    color: #A0AEC0;
}

.dark\:bg-indigo-700 {
    background-color: #4338CA;
    /* Um tom de índigo mais escuro para dark mode */
}

.dark\:bg-gray-900 {
    background-color: #1A202C;
}

/* Se o InputLabel tiver bg-black, você pode querer adicionar o text-white */
.dark\:bg-black {
    background-color: black;
    color: white;
}
</style>