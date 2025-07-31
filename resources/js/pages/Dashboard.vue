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
        <div class="container mx-auto py-8 px-4">
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                    Acesso Rápido
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-lg">
                    Acesse rapidamente os documentos por categoria
                </p>
            </div>
            
            
           

            <div v-if="!props.user.group_ids || props.user.group_ids.length === 0"
                class="mb-8 bg-gradient-to-r from-red-500 to-red-600 text-white p-6 rounded-2xl shadow-lg backdrop-blur-sm max-w-md mx-auto">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                        <span class="text-white font-bold">!</span>
                    </div>
                    <h3 class="font-semibold text-xl">Atenção</h3>
                </div>
                <p class="text-white/90 mb-1">Você não está associado a nenhum grupo.</p>
                <p class="text-white/90">Entre em contato com um Administrador.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">

                <Link v-if="canSeeADLPCard" :href="route('documents.index', {
                    tags: 'ADLP'
                })"
                    class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 rounded-2xl p-6 transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-blue-500/25">
                    
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16 group-hover:scale-110 transition-transform duration-500"></div>
                    
                    <!-- Content -->
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-4 bg-white/20 rounded-xl backdrop-blur-sm transform transition-transform group-hover:scale-110 group-hover:rotate-3">
                                <Scale class="w-8 h-8 text-white drop-shadow-lg" strokeWidth={2.5} />
                            </div>
                            <div class="text-right">
                                <div class="text-3xl font-bold text-white drop-shadow-md">{{ props.totalADLP }}</div>
                                <div class="text-blue-100 text-sm">documentos</div>
                            </div>
                        </div>
                        <h3 class="text-white font-semibold text-lg leading-tight">
                            Atos, Decretos, Leis e Portarias
                        </h3>
                        <p class="text-blue-100 text-sm mt-1 opacity-80">
                            Legislação municipal
                        </p>
                    </div>
                </Link>


                <Link v-if="canSeeRHCard" :href="route('documents.index', { other_metadata: 'RH' })"
                    class="group relative overflow-hidden bg-gradient-to-br from-orange-500 to-orange-700 hover:from-orange-600 hover:to-orange-800 rounded-2xl p-6 transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-orange-500/25">
                    
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16 group-hover:scale-110 transition-transform duration-500"></div>
                    
                    <!-- Content -->
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-4 bg-white/20 rounded-xl backdrop-blur-sm transform transition-transform group-hover:scale-110 group-hover:rotate-3">
                                <FileUser class="w-8 h-8 text-white drop-shadow-lg" strokeWidth={2.5} />
                            </div>
                            <div class="text-right">
                                <div class="text-3xl font-bold text-white drop-shadow-md">{{ documents['RH'] || 0 }}</div>
                                <div class="text-orange-100 text-sm">documentos</div>
                            </div>
                        </div>
                        <h3 class="text-white font-semibold text-lg leading-tight">
                            Recursos Humanos
                        </h3>
                        <p class="text-orange-100 text-sm mt-1 opacity-80">
                            Gestão de pessoal
                        </p>
                    </div>
                </Link>

                <Link v-if="canSeeProcessosCard" :href="route('documents.index', { other_metadata: 'PROCESSO' })"
                    class="group relative overflow-hidden bg-gradient-to-br from-violet-500 to-purple-700 hover:from-violet-600 hover:to-purple-800 rounded-2xl p-6 transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-violet-500/25">
                    
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16 group-hover:scale-110 transition-transform duration-500"></div>
                    
                    <!-- Content -->
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-4 bg-white/20 rounded-xl backdrop-blur-sm transform transition-transform group-hover:scale-110 group-hover:rotate-3">
                                <FolderKanbanIcon class="w-8 h-8 text-white drop-shadow-lg" strokeWidth={2.5} />
                            </div>
                            <div class="text-right">
                                <div class="text-3xl font-bold text-white drop-shadow-md">{{ documents['PROCESSO'] || 0 }}</div>
                                <div class="text-violet-100 text-sm">documentos</div>
                            </div>
                        </div>
                        <h3 class="text-white font-semibold text-lg leading-tight">
                            Processos
                        </h3>
                        <p class="text-violet-100 text-sm mt-1 opacity-80">
                            Protocolo e tramitação
                        </p>
                    </div>
                </Link>

                <Link v-if="canSeeSaudeCard" :href="route('documents.index', { other_metadata: 'SAUDE' })"
                    class="group relative overflow-hidden bg-gradient-to-br from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 rounded-2xl p-6 transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-red-500/25">
                    
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16 group-hover:scale-110 transition-transform duration-500"></div>
                    
                    <!-- Content -->
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-4 bg-white/20 rounded-xl backdrop-blur-sm transform transition-transform group-hover:scale-110 group-hover:rotate-3">
                                <HandHeart class="w-8 h-8 text-white drop-shadow-lg" strokeWidth={2.5} />
                            </div>
                            <div class="text-right">
                                <div class="text-3xl font-bold text-white drop-shadow-md">{{ documents['SAUDE'] || 0 }}</div>
                                <div class="text-red-100 text-sm">documentos</div>
                            </div>
                        </div>
                        <h3 class="text-white font-semibold text-lg leading-tight">
                            Saúde
                        </h3>
                        <p class="text-red-100 text-sm mt-1 opacity-80">
                            Documentos médicos
                        </p>
                    </div>
                </Link>

                <Link v-if="canSeePagamentosCard" :href="route('documents.index', { other_metadata: 'EMPENHO' })"
                    class="group relative overflow-hidden bg-gradient-to-br from-teal-600 to-cyan-700 hover:from-teal-700 hover:to-cyan-800 rounded-2xl p-6 transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-teal-500/25">
                    
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16 group-hover:scale-110 transition-transform duration-500"></div>
                    
                    <!-- Content -->
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-4 bg-white/20 rounded-xl backdrop-blur-sm transform transition-transform group-hover:scale-110 group-hover:rotate-3">
                                <CircleDollarSign class="w-8 h-8 text-white drop-shadow-lg" strokeWidth={2.5} />
                            </div>
                            <div class="text-right">
                                <div class="text-3xl font-bold text-white drop-shadow-md">{{ documents['EMPENHO'] || 0 }}</div>
                                <div class="text-teal-100 text-sm">documentos</div>
                            </div>
                        </div>
                        <h3 class="text-white font-semibold text-lg leading-tight">
                            Pagamentos Orçamentários
                        </h3>
                        <p class="text-teal-100 text-sm mt-1 opacity-80">
                            Controle financeiro
                        </p>
                    </div>
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
/* Animações personalizadas para os cards */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

.group:hover {
    animation: float 3s ease-in-out infinite;
}

/* Efeito glassmorphism */
.backdrop-blur-sm {
    backdrop-filter: blur(8px);
}
</style>