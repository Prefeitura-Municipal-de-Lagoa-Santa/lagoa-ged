<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card'; // Removido CardHeader/CardTitle pois o título virá do pai
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GroupManagerModal from '@/components/modals/GroupManagerModal.vue';

// Interfaces (copiadas do seu código original)
interface User {
    id: string;
    full_name: string;
    username: string;
    email: string;
    is_protected: boolean;
    is_ldap: boolean;
}

interface Group {
    id: string;
    name: string;
}

// PROPS do UserForm.vue
// Ele receberá o 'form' do Inertia e os dados iniciais, além de allGroups
interface Props {
    // A prop 'form' virá do useForm no componente pai (UserEdit.vue ou UserCreate.vue)
    form: ReturnType<typeof useForm>; // Tipagem para o objeto useForm do Inertia
    
    // O objeto user virá do componente pai (para desabilitar campos e exibir nome)
    // Para userCreate, será um objeto 'novo' com valores padrão.
    user: User | null; // Pode ser null para o modo criação
    
    // Todos os grupos disponíveis para o modal
    allGroups: Group[]; 
    
    // Booleano para saber se estamos no modo de edição (ex: para mostrar/ocultar campos de senha)
    isEditing: boolean;
}

const props = defineProps<Props>();

const isModalOpen = ref(false);

// Handler para atualização de grupos vindo do modal
function handleGroupUpdate(updatedGroups: Group[]) {
    props.form.userGroups = updatedGroups;
}

// Emitir evento 'submit' para o componente pai
const emit = defineEmits(['submit']);

function submitForm() {
    emit('submit'); // Emite o evento para o pai lidar com o PUT ou POST
}

// Campos desabilitados para usuários LDAP ou protegidos
const isDisabled = computed(() => props.user ? props.user.is_ldap || props.user.is_protected : false);

// Texto do botão de salvar
const buttonText = computed(() => {
    if (props.form.processing) {
        return 'Salvando...';
    }
    return props.isEditing ? 'Salvar Alterações' : 'Criar Usuário';
});

</script>

<template>
    <form @submit.prevent="submitForm">
        <Card>
            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="gap-2 md:col-span-2">
                    <Label class="h-8" for="full_name">Nome</Label>
                    <Input :disabled="isDisabled" id="full_name" type="text" v-model="props.form.full_name" />
                    <div v-if="props.form.errors.full_name" class="text-sm text-red-500">{{ props.form.errors.full_name }}</div>
                </div>

                <div class="grid gap-3">
                    <Label for="username">Usuário</Label>
                    <Input :disabled="props.isEditing && isDisabled" id="username" type="text" v-model="props.form.username" />
                    <div v-if="props.form.errors.username" class="text-sm text-red-500">{{ props.form.errors.username }}</div>
                </div>

                <div class="grid gap-3">
                    <Label for="email">E-mail</Label>
                    <Input :disabled="isDisabled" id="email" v-model="props.form.email" />
                    <div v-if="props.form.errors.email" class="text-sm text-red-500">{{ props.form.errors.email }}</div>
                </div>
                <div v-if="!props.isEditing" class="grid gap-3">
                    <Label for="password">Senha</Label>
                    <Input :disabled="isDisabled" id="password" type="password" v-model="props.form.password" />
                    <div v-if="props.form.errors.password" class="text-sm text-red-500">{{ props.form.errors.password }}</div>
                </div>
                <div v-if="!props.isEditing" class="grid gap-3">
                    <Label for="password_confirmation">Confirme a Senha</Label>
                    <Input :disabled="isDisabled" id="password_confirmation" type="password" v-model="props.form.password_confirmation" />
                    <div v-if="props.form.errors.password_confirmation" class="text-sm text-red-500">{{ props.form.errors.password_confirmation }}</div>
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <div class="flex justify-between items-center mb-2">
                        <Label>Grupos</Label>
                        <Button type="button" variant="outline" size="sm" @click="isModalOpen = true"
                        > {{ props.isEditing ? 'Editar Grupos' :'Adicionar Grupos' }}
                        </Button>
                    </div>

                    <div v-if="props.form.userGroups && props.form.userGroups.length > 0"
                         class="flex flex-wrap gap-2 rounded-lg border bg-muted/50 p-3 min-h-[40px] items-center">
                        <span v-for="group in props.form.userGroups" :key="group.id"
                              class="inline-flex items-center rounded-full bg-muted px-10 py-1 text-sm font-semibold shadow-sm">
                            {{ group.name }}
                        </span>
                    </div>

                    <div v-else
                         class="flex items-center justify-center rounded-lg border p-3 min-h-[40px] text-sm text-muted-foreground">
                        <span>Este usuário não pertence a nenhum grupo.</span>
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

    <GroupManagerModal 
        v-model="isModalOpen"
        :all-groups="props.allGroups"
        :initial-selected-groups="props.form.userGroups"
        :username="props.user ? props.user.username : 'Novo Usuário'" @confirm="handleGroupUpdate"
    />
</template>