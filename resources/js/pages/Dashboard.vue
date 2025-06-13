<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'; // Ajuste o caminho se necessário
import { Head } from '@inertiajs/vue3';

// Se você estiver usando a forma mais moderna de definir layouts persistentes no app.js
// ou via defineOptions, você não precisaria importar e envolver aqui.
// Mas para um exemplo explícito:
// defineOptions({ layout: DashboardLayout }); // Opção 1: se configurado para funcionar assim

</script>

<template>

    <Head title="Dashboard" />

    <DashboardLayout>
        <div class="container mx-auto py-8">
            <h1 class="text-3xl font-bold text-foreground mb-6">Meu Dashboard</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-blue-500 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <h2 class="text-blue-100 text-xl font-semibold">Atos, Decretos, Leis e Portarias</h2>
                    <p class="text-blue-100 p-5 mt-3 flex-grow flex items-center justify-center text-3xl font-bold">900
                    </p>
                </div>

                <div class="bg-red-500 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <h2 class="text-red-100 text-xl font-semibold">Recursos Humanos</h2>
                    <p class="text-red-100 p-5 mt-3 flex-grow flex items-center justify-center text-3xl font-bold">300
                    </p>
                </div>

                <div class="bg-yellow-400 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <h2 class="text-yellow-50 text-xl font-semibold">Projetos</h2>
                    <p class="text-yellow-50 p-5 mt-3 flex-grow flex items-center justify-center text-3xl font-bold">100
                    </p>
                </div>

                <div class="bg-green-500 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <h2 class="text-green-100 text-xl font-semibold">Saúde</h2>
                    <p class="text-green-100 p-5 mt-3 flex-grow flex items-center justify-center text-3xl font-bold">200
                    </p>
                </div>

                <div
                    class="bg-fuchsia-700 p-6 rounded-lg shadow flex flex-col justify-between items-center text-center">
                    <h2 class="text-fuchsia-200 text-xl font-semibold">Pagamentos Orçamentários</h2>
                    <p class="text-fuchsia-200 p-5 mt-3 flex-grow flex items-center justify-center text-3xl font-bold">
                        100</p>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>