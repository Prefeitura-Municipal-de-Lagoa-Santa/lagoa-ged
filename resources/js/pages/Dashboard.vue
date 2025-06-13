<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'; // Ajuste o caminho se necessário
import { Head } from '@inertiajs/vue3';
import { BadgeDollarSign, Banknote, CircleDollarSign, Coins, DollarSign, FileUser, FolderKanbanIcon, HandHeart, Scale } from 'lucide-vue-next';

// Se você estiver usando a forma mais moderna de definir layouts persistentes no app.js
// ou via defineOptions, você não precisaria importar e envolver aqui.
// Mas para um exemplo explícito:
// defineOptions({ layout: DashboardLayout }); // Opção 1: se configurado para funcionar assim

</script>

<template>

    <Head title="Dashboard" />

    <DashboardLayout>
        <div class="container mx-auto py-8">
            <h1 class="text-3xl font-bold text-foreground mb-6">Acesso Rápido</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-blue-500 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <Scale class="text-blue-900 h-12 w-12" />
                    <p class="text-blue-100 mt-3  items-center justify-center text-3xl font-bold">900
                    <h2 class="text-blue-100 text-2xl font-semibold p-4">Atos, Decretos, Leis e Portarias</h2>
                    </p>
                </div>

                <div class="bg-red-500 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <FileUser class="text-red-900 h-12 w-12" />
                    <p class="text-red-100 mt-3 items-center justify-center text-3xl font-bold">300
                    <h2 class="text-red-100 text-2xl font-semibold p-4">Recursos Humanos</h2>
                    </p>
                </div>

                <div class="bg-yellow-400 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <FolderKanbanIcon class="text-yellow-900 h-12 w-12" />
                    <p class="text-yellow-50 mt-3 items-center justify-center text-3xl font-bold">100
                    <h2 class="text-yellow-50 text-2xl font-semibold p-4">Projetos</h2>
                    </p>
                </div>

                <div class="bg-green-500 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <HandHeart class="text-green-900 h-12 w-12" />
                    <p class="text-green-100 mt-3 items-center justify-center text-3xl font-bold">200
                    <h2 class="text-green-100 text-2xl font-semibold p-4">Saúde</h2>
                    </p>
                </div>

                <div
                class="bg-fuchsia-500 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                <CircleDollarSign class="text-fuchsia-900 h-12 w-12" />
                <p class="text-fuchsia-200 mt-3 items-center justify-center text-3xl font-bold">100</p>
                <h2 class="text-fuchsia-200 text-2xl font-semibold p-4">Pagamentos Orçamentários</h2>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>