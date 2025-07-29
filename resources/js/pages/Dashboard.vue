<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CircleDollarSign, FileUser, FolderKanbanIcon, HandHeart, Scale } from 'lucide-vue-next';

import { defineProps, computed } from 'vue'; // Removendo 'computed' pois não será mais necessário para somaADLP

const props = defineProps({
    documents: {
        type: Object,
        required: true,
    },
    totalADLP: { // Nova prop para o total de ADLP
        type: Number,
        default: 0,
    },
    user: {
        type: Object,
        required: true, // Certifique-se de que o usuário é passado corretamente
    },
    groups: {
        type: Array,
        default: () => [],
    }
});

// Helper computed property para verificar se o usuário é admin
const isAdmin = computed(() => {
    return props.user?.is_admin === true;
});

// Função auxiliar para verificar se o usuário pertence a um grupo específico
// Recebe o nome do grupo como string (ex: 'RECURSOS HUMANOS')
const userBelongsToGroup = (groupName) => {
    console.log(`--- Checking userBelongsToGroup for "${groupName}" ---`);

    // 1. Verificação de Admin
    if (isAdmin.value) {
        console.log(`  User is Admin. Permitting access to "${groupName}".`);
        return true; // Admin vê tudo
    }

    // 2. Verificação de Usuário sem Grupos (Adicionado para clareza)
    // Usamos a computed property userHasNoGroups aqui.
    if (props.user.group_ids.length === 0) {
        console.log(`  User has no groups. Denying access to "${groupName}".`);
        return false;
    }

    // 3. Encontrar o Grupo Alvo
    const targetGroup = props.groups.find(group => group.name === groupName);
    console.log(`  Searching for target group "${groupName}". Found:`, targetGroup);

    if (!targetGroup) {
        console.warn(`  WARNING: Group "${groupName}" not found in 'props.groups'. Denying access.`);
        console.log(`  Available groups:`, props.groups); // Mostra os grupos disponíveis
        return false;
    }

    // 4. Preparar IDs para Comparação
    // É provável que o erro esteja aqui: _id vs id
    const targetGroupConvertedId = String(targetGroup.id); // Corrigido para ._id
    console.log(`  Target Group ID (from props.groups): ${targetGroup.id}`);
    console.log(`  Target Group ID (converted to string): ${targetGroupConvertedId}`);

    // Garante que props.user.group_ids é um array e mapeia seus elementos para string
    const userGroupIdsConverted = props.user.group_ids.map(group => group.$oid);
    console.log(`  User's group IDs (from props.user.group_ids):`, props.user.group_ids);
    console.log(`  User's group IDs (converted to strings):`, userGroupIdsConverted);

    // 5. Realizar a Comparação
    const hasAccess = userGroupIdsConverted.includes(targetGroupConvertedId);
    console.log(`  Does user's groups include target group ID? ${hasAccess}`);
    console.log(`--- End checking for "${groupName}" ---`);

    return hasAccess;
};

// Computed properties para a visibilidade de cada card
const canSeeADLPCard = computed(() => {
    // Exemplo: ADLP pode ser visível para todos (ou para um grupo específico, ajuste conforme sua regra)
    // Se 'ADLP' não corresponde a um grupo, mas a um tipo de documento, você pode ter uma regra diferente.
    // Se a regra é que Admin vê, e outros veem se tiver a tag, então não é necessário um grupo específico aqui.
    // Mas se ADLP só é visível para "Administradores" ou "Leitores de ADLP", então:
    return isAdmin.value || userBelongsToGroup('ADLP'); // Exemplo: crie um grupo 'LEITORES_ADLP'
});

const canSeeRHCard = computed(() => {
    return isAdmin.value || userBelongsToGroup('RH'); // Exemplo
});

const canSeeProcessosCard = computed(() => {
    return isAdmin.value || userBelongsToGroup('PROCESSOS'); // Ajustei para 'PROJETOS' baseado no seu 'FolderKanbanIcon' e label 'Projetos' antes. Se for 'PROCESSOS', mude.
});

const canSeeSaudeCard = computed(() => {
    return isAdmin.value || userBelongsToGroup('SAUDE'); // Exemplo
});

const canSeePagamentosCard = computed(() => {
    return isAdmin.value || userBelongsToGroup('FINANCEIRO'); // Exemplo
});


</script>

<template>

    <Head title="Dashboard" />

    <DashboardLayout>
        <div class="container mx-auto py-8">
            <h1 class="text-3xl font-bold text-foreground mb-6">Acesso Rápido</h1>
            
            
           

            <div v-if="!props.user.group_ids || props.user.group_ids.length === 0"
                class="shadow-md shadow-gray-500 dark:shadow-black bg-red-600 text-red-100 p-6 rounded-lg flex flex-col items-center text-center max-w-md mx-auto">
                <p class="font-semibold text-xl mb-2">Aviso:</p>
                <p class="text-lg">Você não está associado a nenhum grupo.</p>
                <p class="text-lg">Por favor, entre em contato com um Administrador.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">

                <Link v-if="canSeeADLPCard" :href="route('documents.index', {
                    tags: 'ADLP'
                })"
                    class="shadow-md shadow-gray-500 dark:shadow-black   bg-blue-600 p-3 rounded-lg shadow flex flex-col justify-between items-center text-center hover:opacity-90 transition-opacity cursor-pointer">
                <Scale class="text-blue-100 h-12 w-12" />
                <p class="text-blue-100 mt-3 items-center justify-center text-3xl font-bold">{{ props.totalADLP }}</p>
                <h2 class="text-blue-100 text-2xl font-semibold p-1">Atos, Decretos, Leis e Portarias</h2>
                </Link>


                <Link v-if="canSeeRHCard" :href="route('documents.index', { other_metadata: 'RH' })"
                    class="shadow-md shadow-gray-500 dark:shadow-black   bg-red-600 p-3 rounded-lg shadow flex flex-col justify-between items-center text-center hover:opacity-90 transition-opacity cursor-pointer">
                <FileUser class="text-red-100 h-12 w-12" />
                <p class="text-red-100 mt-3 items-center justify-center text-3xl font-bold">{{ documents['RH'] || 0 }}
                </p>
                <h2 class="text-red-100 text-2xl font-semibold p-1">Recursos Humanos</h2>
                </Link>

                <Link v-if="canSeeProcessosCard" :href="route('documents.index', { other_metadata: 'PROCESSO' })"
                    class="shadow-md shadow-gray-500 dark:shadow-black  bg-yellow-400 p-3 rounded-lg shadow flex flex-col justify-between items-center text-center hover:opacity-90 transition-opacity cursor-pointer">
                <FolderKanbanIcon class="text-yellow-50 h-12 w-12" />
                <p class="text-yellow-50 mt-3 items-center justify-center text-3xl font-bold">{{ documents['PROCESSO']
                    || 0 }}</p>
                <h2 class="text-yellow-50 text-2xl font-semibold p-1">Processos</h2>
                </Link>

                <Link v-if="canSeeSaudeCard" :href="route('documents.index', { other_metadata: 'SAUDE' })"
                    class="shadow-md shadow-gray-500 dark:shadow-black  bg-green-600 p-3 rounded-lg shadow flex flex-col justify-between items-center text-center hover:opacity-90 transition-opacity cursor-pointer">
                <HandHeart class="text-green-100 h-12 w-12" />
                <p class="text-green-100 mt-3 items-center justify-center text-3xl font-bold">{{ documents['SAUDE']
                    || 0 }}</p>
                <h2 class="text-green-100 text-2xl font-semibold p-1">Saúde</h2>
                </Link>

                <Link v-if="canSeePagamentosCard" :href="route('documents.index', { other_metadata: 'EMPENHO' })"
                    class="shadow-md shadow-gray-500 dark:shadow-black  bg-fuchsia-600 p-3 rounded-lg shadow flex flex-col justify-between items-center text-center hover:opacity-90 transition-opacity cursor-pointer">
                <CircleDollarSign class="text-fuchsia-100 h-12 w-12" />
                <p class="text-fuchsia-100 mt-3 items-center justify-center text-3xl font-bold">
                    {{ documents['EMPENHO'] || 0 }}</p>
                <h2 class="text-fuchsia-100 text-2xl font-semibold p-1">Pagamentos Orçamentários</h2>
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>