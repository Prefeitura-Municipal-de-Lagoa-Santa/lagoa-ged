<script setup lang="ts">
import { ArrowRight, ArrowLeft, ArrowUp, ArrowDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card'; // Importar apenas o que é usado aqui
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3'; // Importar useForm para tipagem

// Interfaces (copiadas para garantir tipagem correta)
interface User {
    id: string;
    full_name: string;
    username: string;
    email: string;
    // Adicione outras propriedades de User se necessário (ex: is_protected, is_ldap)
}

interface Group {
    id: string;
    name: string;
    description: string;
    members: User[];
    is_protected?: boolean; // Adicione is_protected se existir no seu modelo Group
}

// PROPS do GroupForm.vue
interface Props {
    form: ReturnType<typeof useForm>; // O objeto useForm do Inertia
    allUsers: User[]; // Todos os usuários disponíveis
    isEditing: boolean; // Flag para indicar se é modo de edição ou criação
    group?: Group; // Opcional para o modo de criação, obrigatório para edição
}

const props = defineProps<Props>();

// Ref's para a seleção dos usuários nas caixas de listagem
const selectedAvailable = ref<string[]>([]);
const selectedMembers = ref<string[]>([]);

// Helper para garantir que user_ids é sempre um array de strings
function getUserIds(): string[] {
    const ids = props.form.user_ids;
    if (Array.isArray(ids)) {
        return ids.map(id => String(id));
    }
    return [];
}

// Usuários que ainda não são membros do grupo
const availableUsers = computed(() => {
    const userIds = getUserIds();
    return props.allUsers.filter(user => !userIds.includes(user.id));
});

// Usuários que são membros do grupo
const groupMembers = computed(() => {
    const userIds = getUserIds();
    return props.allUsers.filter(user => userIds.includes(user.id));
});

// Função para adicionar membros do lado esquerdo para o direito
function addMembers() {
    let userIds = getUserIds();
    userIds = [...userIds, ...selectedAvailable.value];
    props.form.user_ids = userIds;
    selectedAvailable.value = [];
}

// Função para remover membros do lado direito para o esquerdo
function removeMembers() {
    let userIds = getUserIds();
    userIds = userIds.filter(id => !selectedMembers.value.includes(id));
    props.form.user_ids = userIds;
    selectedMembers.value = [];
}

// Emite o evento 'submit' para o componente pai
const emit = defineEmits(['submit']);

function submitForm() {
    emit('submit'); // Emite o evento para o pai lidar com o PUT ou POST
}

// Propriedade computada para desabilitar campos se o grupo for protegido (apenas no modo de edição)
const isDisabled = computed(() => props.isEditing && props.group?.is_protected);

// Texto do botão de salvar
const buttonText = computed(() => {
    if (props.form.processing) {
        return 'Salvando...';
    }
    return props.isEditing ? 'Salvar Alterações' : 'Criar Grupo';
});

</script>

<template>
    <form @submit.prevent="submitForm">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name" class="text-base font-semibold text-gray-700 dark:text-gray-200">Nome</Label>
                <Input :disabled="isDisabled" class="uppercase bg-indigo-50 dark:bg-zinc-800 border-2 border-indigo-300 dark:border-indigo-700 rounded-xl px-4 py-2 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500" id="name" type="text" v-model="props.form.name" />
                <div v-if="props.form.errors.name" class="text-sm text-red-500">{{ props.form.errors.name }}</div>
            </div>

            <div class="grid gap-2">
                <Label for="description" class="text-base font-semibold text-gray-700 dark:text-gray-200">Descrição</Label>
                <Textarea :disabled="isDisabled" id="description" v-model="props.form.description" class="bg-indigo-50 dark:bg-zinc-800 border-2 border-indigo-300 dark:border-indigo-700 rounded-xl px-4 py-2 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500" />
                <div v-if="props.form.errors.description" class="text-sm text-red-500">{{ props.form.errors.description }}</div>
            </div>

            <div class="grid gap-2">
                <Label class="text-base font-semibold text-gray-700 dark:text-gray-200">Membros do Grupo</Label>
                <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] items-center gap-4">
                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-center text-gray-700 dark:text-gray-200">Usuários Disponíveis</span>
                        <select multiple class="bg-indigo-50 dark:bg-zinc-800 border-2 border-indigo-300 dark:border-indigo-700 rounded-xl h-48 p-2 text-gray-900 dark:text-white" v-model="selectedAvailable">
                            <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                {{ user.full_name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Button type="button" @click="addMembers" :disabled="selectedAvailable.length === 0" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-xl transition-all duration-200 flex items-center justify-center">
                            <ArrowRight class="hidden md:inline w-5 h-5" />
                            <ArrowUp class="md:hidden w-5 h-5" />
                        </Button>
                        <Button type="button" @click="removeMembers" :disabled="selectedMembers.length === 0" variant="destructive" class="px-4 py-2 rounded-xl flex items-center justify-center">
                            <ArrowLeft class="hidden md:inline w-5 h-5" />
                            <ArrowDown class="md:hidden w-5 h-5" />
                        </Button>
                    </div>

                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-center text-gray-700 dark:text-gray-200">Membros no Grupo</span>
                        <select multiple class="bg-indigo-50 dark:bg-zinc-800 border-2 border-indigo-300 dark:border-indigo-700 rounded-xl h-48 p-2 text-gray-900 dark:text-white" v-model="selectedMembers">
                            <option v-for="user in groupMembers" :key="user.id" :value="user.id">
                                {{ user.full_name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div v-if="props.form.errors.user_ids" class="text-sm text-red-500 mt-2">
                    {{ props.form.errors.user_ids }}
                </div>
            </div>

            <div class="flex justify-end">
                <Button type="submit" :disabled="props.form.processing" class="px-8 py-3 text-lg rounded-xl shadow-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold transition-all duration-200">
                    {{ buttonText }}
                </Button>
            </div>
        </div>
    </form>
</template>