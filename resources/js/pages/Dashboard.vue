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
                <div class="bg-card p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold text-foreground">Card 1</h2>
                    <p class="text-muted-foreground mt-2">Conteúdo do card 1...</p>
                </div>
                <div class="bg-card p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold text-foreground">Card 2</h2>
                    <p class="text-muted-foreground mt-2">Conteúdo do card 2...</p>
                </div>
                <div class="bg-card p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold text-foreground">Card 3</h2>
                    <p class="text-muted-foreground mt-2">Conteúdo do card 3...</p>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>