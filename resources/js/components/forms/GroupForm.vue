<script setup lang="ts">
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

// Usuários que ainda não são membros do grupo
const availableUsers = computed(() => {
    // Filtra allUsers para incluir apenas aqueles cujo ID não está no form.user_ids
    return props.allUsers.filter(user => !props.form.user_ids.includes(user.id));
});

// Usuários que são membros do grupo
const groupMembers = computed(() => {
    // Filtra allUsers para incluir apenas aqueles cujo ID está no form.user_ids
    return props.allUsers.filter(user => props.form.user_ids.includes(user.id));
});

// Função para adicionar membros do lado esquerdo para o direito
function addMembers() {
    props.form.user_ids.push(...selectedAvailable.value);
    selectedAvailable.value = []; // Limpa a seleção
}

// Função para remover membros do lado direito para o esquerdo
function removeMembers() {
    props.form.user_ids = props.form.user_ids.filter(id => !selectedMembers.value.includes(id));
    selectedMembers.value = []; // Limpa a seleção
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
        <Card>
            <CardContent class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Nome</Label>
                    <Input :disabled="isDisabled" class="uppercase" id="name" type="text" v-model="props.form.name" />
                    <div v-if="props.form.errors.name" class="text-sm text-red-500">{{ props.form.errors.name }}</div>
                </div>

                <div class="grid gap-2">
                    <Label for="description">Descrição</Label>
                    <Textarea :disabled="isDisabled" id="description" v-model="props.form.description" />
                    <div v-if="props.form.errors.description" class="text-sm text-red-500">{{ props.form.errors.description }}</div>
                </div>

                <div class="grid gap-2">
                    <Label>Membros do Grupo</Label>
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] items-center gap-4">
                        <div class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-center">Usuários Disponíveis</span>
                            <select multiple class="bg-card border rounded-md h-48 p-2"
                                v-model="selectedAvailable">
                                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                    {{ user.full_name }}
                                </option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-2">
                            <Button type="button" @click="addMembers" :disabled="selectedAvailable.length === 0">
                                &gt;&gt;
                            </Button>
                            <Button type="button" @click="removeMembers" :disabled="selectedMembers.length === 0" variant="destructive">
                                &lt;&lt;
                            </Button>
                        </div>

                        <div class="flex flex-col gap-2">
                            <span class="text-sm font-medium text-center">Membros no Grupo</span>
                            <select multiple class="bg-card border rounded-md h-48 p-2"
                                v-model="selectedMembers">
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
            </CardContent>

            <CardFooter class="flex justify-end">
                <Button type="submit" :disabled="props.form.processing">
                    {{ buttonText }}
                </Button>
            </CardFooter>
        </Card>
    </form>
</template>