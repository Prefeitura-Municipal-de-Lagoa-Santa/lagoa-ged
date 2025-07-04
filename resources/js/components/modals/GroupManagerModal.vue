<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ChevronRight, ChevronLeft, ChevronDown, ChevronUp } from 'lucide-vue-next';
import Label from '../ui/label/Label.vue';

// Interfaces necessárias para as props
interface Group {
    id: string;
    name: string;
};

// Definindo as PROPS que o componente receberá do pai (Edit.vue)
const props = defineProps<{
    modelValue: boolean; // Para controlar a visibilidade com v-model
    allGroups: Group[];
    initialSelectedGroups: Group[];
    username: string;
}>();

// Definindo os EVENTOS que o componente emitirá para o pai
const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void; // Para fechar o modal com v-model
    (e: 'confirm', selectedGroups: Group[]): void; // Para enviar a lista final de grupos
}>();

// Estado interno do componente
const availableGroups = ref<Group[]>([]);
const selectedGroups = ref<Group[]>([]);
const groupsToAdd = ref<string[]>([]);
const groupsToRemove = ref<string[]>([]);

/**
 * Wrapper para o v-model. Permite que o componente feche a si mesmo
 * emitindo um evento para o componente pai.
 */
const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

/**
 * Popula as listas do modal sempre que ele é aberto.
 * Usamos 'watch' para reagir à mudança da prop 'modelValue'.
 */
function setupModalState() {
    // Clona os dados recebidos para não modificar as props diretamente
    selectedGroups.value = JSON.parse(JSON.stringify(props.initialSelectedGroups));

    const selectedIds = new Set(selectedGroups.value.map(g => g.id));
    availableGroups.value = props.allGroups.filter(g => !selectedIds.has(g.id));

    // Limpa seleções de cliques anteriores
    groupsToAdd.value = [];
    groupsToRemove.value = [];
}

// Observa a prop 'modelValue'. Quando ela se torna 'true', o modal está abrindo.
watch(() => props.modelValue, (newValue) => {
    if (newValue) {
        setupModalState();
    }
});


// Funções para mover os grupos (lógica robusta com splice)
function addSelectedGroups() {
    groupsToAdd.value.forEach(idToMove => {
        const index = availableGroups.value.findIndex(group => group.id === idToMove);
        if (index > -1) {
            const [groupToMove] = availableGroups.value.splice(index, 1);
            selectedGroups.value.push(groupToMove);
        }
    });
    groupsToAdd.value = [];
    selectedGroups.value.sort((a, b) => a.name.localeCompare(b.name));
}

function removeSelectedGroups() {
    groupsToRemove.value.forEach(idToMove => {
        const index = selectedGroups.value.findIndex(group => group.id === idToMove);
        if (index > -1) {
            const [groupToMove] = selectedGroups.value.splice(index, 1);
            availableGroups.value.push(groupToMove);
        }
    });
    groupsToRemove.value = [];
    availableGroups.value.sort((a, b) => a.name.localeCompare(b.name));
}

/**
 * Ao confirmar, emite o evento 'confirm' com a lista atualizada de grupos
 * e depois fecha o modal.
 */
function handleConfirm() {
    emit('confirm', selectedGroups.value);
    isOpen.value = false; // Fecha o modal
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="flex flex-col max-h-[100vh] max-w-full md:max-w-4xl">
            <DialogHeader>
                <DialogTitle>Gerenciar Grupos de {{ props.username }}</DialogTitle>
                <DialogDescription>
                    Mova os grupos entre as listas para definir quais o usuário pertence.
                </DialogDescription>
            </DialogHeader>

            <div class="flex-1 min-h-0 overflow-y-auto pr-2 flex flex-col md:grid md:grid-cols-11 gap-4 py-4 md:items-center">
                
                <div class="md:col-span-5">
                    <Label class="mb-2 block text-center">Grupos Disponíveis</Label>
                    <select v-model="groupsToAdd" multiple class="w-full h-33 sm:h-48 md:h-64 border rounded-md p-2 bg-background">
                        <option v-for="group in availableGroups" :key="group.id" :value="group.id">
                            {{ group.name }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-row-reverse justify-center gap-4 md:flex-col md:col-span-1">
                    <Button type="button" @click="addSelectedGroups" :disabled="groupsToAdd.length === 0">
                        <ChevronDown class="h-4 w-4 md:hidden" />
                        <ChevronRight class="h-4 w-4 hidden md:flex" />
                    </Button>
                    <Button type="button" variant="destructive" @click="removeSelectedGroups" :disabled="groupsToRemove.length === 0">
                        <ChevronUp class="h-4 w-4 md:hidden" />
                        <ChevronLeft class="h-4 w-4 hidden md:flex" />
                    </Button>
                </div>

                <div class="md:col-span-5">
                    <Label class="mb-2 block text-center">Membro De</Label>
                     <select v-model="groupsToRemove" multiple class="w-full h-33 sm:h-48 md:h-64 border rounded-md p-2 bg-background">
                        <option v-for="group in selectedGroups" :key="group.id" :value="group.id">
                            {{ group.name }}
                        </option>
                    </select>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="ghost" @click="isOpen = false">Cancelar</Button>
                <Button type="button" @click="handleConfirm">Confirmar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>