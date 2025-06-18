<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'; // Ajuste o caminho se necessário
import { Head } from '@inertiajs/vue3';
import { CircleDollarSign, FileUser, FolderKanbanIcon, HandHeart, Scale } from 'lucide-vue-next';

// Se você estiver usando a forma mais moderna de definir layouts persistentes no app.js
// ou via defineOptions, você não precisaria importar e envolver aqui.
// Mas para um exemplo explícito:
// defineOptions({ layout: DashboardLayout }); // Opção 1: se configurado para funcionar assim

import { defineProps, computed } from 'vue';

const props = defineProps({
  documents: {
    type: Object,
    required: true,
  }
});

const somaADLP = computed(() => {
  // 1. Defina exatamente as chaves que você quer somar
  const tiposParaSomar = ['DECRETO', 'ATO', 'LEI', 'PORTARIA'];

  // 2. Use 'reduce' para somar os valores
  return tiposParaSomar.reduce((somaTotal, tipoAtual) => {
    // Para cada tipo, pega a contagem do objeto 'documents' (ou 0 se não existir)
    const contagem = props.documents[tipoAtual] || 0;
    
    // Adiciona a contagem à soma total
    return somaTotal + contagem;
  }, 0); // O '0' aqui é o valor inicial da soma
});

</script>

<template>

    <Head title="Dashboard" />

    <DashboardLayout>
        <div class="container mx-auto py-8">
            <h1 class="text-3xl font-bold text-foreground mb-6">Acesso Rápido</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-blue-500 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <Scale class="text-blue-900 h-12 w-12" />
                    <p class="text-blue-100 mt-3  items-center justify-center text-3xl font-bold">{{ somaADLP }}</p>
                    <h2 class="text-blue-100 text-2xl font-semibold p-4">Atos, Decretos, Leis e Portarias</h2>
                </div>

                <div class="bg-red-500 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <FileUser class="text-red-900 h-12 w-12" />
                    <p class="text-red-100 mt-3 items-center justify-center text-3xl font-bold">{{documents['RH'] || 0 }}</p>
                    <h2 class="text-red-100 text-2xl font-semibold p-4">Recursos Humanos</h2>
                </div>

                <div class="bg-yellow-400 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <FolderKanbanIcon class="text-yellow-900 h-12 w-12" />
                    <p class="text-yellow-50 mt-3 items-center justify-center text-3xl font-bold">{{documents['PROJETO'] || 0 }}</p>
                    <h2 class="text-yellow-50 text-2xl font-semibold p-4">Projetos</h2>
                </div>

                <div class="bg-green-500 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <HandHeart class="text-green-900 h-12 w-12" />
                    <p class="text-green-100 mt-3 items-center justify-center text-3xl font-bold">{{documents['SAUDE'] || 0 }}</p>
                    <h2 class="text-green-100 text-2xl font-semibold p-4">Saúde</h2>
                </div>

                <div
                    class="bg-fuchsia-500 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <CircleDollarSign class="text-fuchsia-900 h-12 w-12" />
                    <p class="text-fuchsia-200 mt-3 items-center justify-center text-3xl font-bold">{{documents['EMPENHO'] || 0 }}</p>
                    <h2 class="text-fuchsia-200 text-2xl font-semibold p-4">Pagamentos Orçamentários</h2>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>