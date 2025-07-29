<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CircleDollarSign, FileUser, FolderKanbanIcon, HandHeart, Scale } from 'lucide-vue-next';

import { defineProps } from 'vue'; // Removendo 'computed' pois não será mais necessário para somaADLP

const props = defineProps({
    documents: {
        type: Object,
        required: true,
    },
    totalADLP: { // Nova prop para o total de ADLP
        type: Number,
        default: 0,
    }
});


</script>

<template>

    <Head title="Dashboard" />

    <DashboardLayout>
        <div class="container mx-auto py-8">
            <h1 class="text-3xl font-bold text-foreground mb-6">Acesso Rápido</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">

                <Link :href="route('documents.index', {
                    tags: 'ADLP'
                })"
                    class="shadow-md shadow-gray-500 dark:shadow-black   bg-blue-600 p-3 rounded-lg shadow flex flex-col justify-between items-center text-center hover:opacity-90 transition-opacity cursor-pointer">
                <Scale class="text-blue-100 h-12 w-12" />
                <p class="text-blue-100 mt-3 items-center justify-center text-3xl font-bold">{{ props.totalADLP }}</p>
                <h2 class="text-blue-100 text-2xl font-semibold p-1">Atos, Decretos, Leis e Portarias</h2>
                </Link>


                <Link :href="route('documents.index', { other_metadata: 'RH' })"
                    class="shadow-md shadow-gray-500 dark:shadow-black   bg-red-600 p-3 rounded-lg shadow flex flex-col justify-between items-center text-center hover:opacity-90 transition-opacity cursor-pointer">
                <FileUser class="text-red-100 h-12 w-12" />
                <p class="text-red-100 mt-3 items-center justify-center text-3xl font-bold">{{ documents['RH'] || 0 }}
                </p>
                <h2 class="text-red-100 text-2xl font-semibold p-1">Recursos Humanos</h2>
                </Link>

                <Link :href="route('documents.index', { other_metadata: 'PROCESSO' })"
                    class="shadow-md shadow-gray-500 dark:shadow-black  bg-yellow-400 p-3 rounded-lg shadow flex flex-col justify-between items-center text-center hover:opacity-90 transition-opacity cursor-pointer">
                <FolderKanbanIcon class="text-yellow-50 h-12 w-12" />
                <p class="text-yellow-50 mt-3 items-center justify-center text-3xl font-bold">{{ documents['PROCESSO']
                    || 0 }}</p>
                <h2 class="text-yellow-50 text-2xl font-semibold p-1">Processos</h2>
                </Link>

                <Link :href="route('documents.index', { other_metadata: 'SAUDE' })"
                    class="shadow-md shadow-gray-500 dark:shadow-black  bg-green-600 p-3 rounded-lg shadow flex flex-col justify-between items-center text-center hover:opacity-90 transition-opacity cursor-pointer">
                <HandHeart class="text-green-100 h-12 w-12" />
                <p class="text-green-100 mt-3 items-center justify-center text-3xl font-bold">{{ documents['SAUDE']
                    || 0 }}</p>
                <h2 class="text-green-100 text-2xl font-semibold p-1">Saúde</h2>
                </Link>

                <Link :href="route('documents.index', { other_metadata: 'EMPENHO' })"
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