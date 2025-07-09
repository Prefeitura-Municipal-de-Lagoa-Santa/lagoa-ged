<script setup lang="ts">
defineProps<{
  cardTitle?: string; // Título para o card, ex: "Login", "Registrar"
}>();
</script>

<template>
  <div class="min-h-screen flex flex-col justify-center items-center p-4 relative overflow-hidden bg-background">
    <div
      class="absolute top-[10%] right-[53%] w-96 h-96 md:w-[300px] md:h-[300px] bg-gradient-to-br from-teal-300 to-sky-500 rounded-full opacity-20 dark:opacity-10 animate-pulse"
      style="animation-duration: 10s;"></div>
    <div
      class="absolute bottom-[15%] left-[53%] w-80 h-80 md:w-[250px] md:h-[250px] bg-gradient-to-tl from-purple-400 to-pink-400 rounded-full opacity-20 dark:opacity-10 animate-pulse"
      style="animation-duration: 12s; animation-delay: 1s;"></div>
    <div class="absolute top-[30%] right-[35%] w-12 h-12 bg-blue-400 rounded-full opacity-30 dark:opacity-20"></div>
    <div class="absolute bottom-[30%] left-[35%] w-8 h-8 bg-teal-300 rounded-full opacity-40 dark:opacity-20"></div>

    <div class="relative z-10 w-full max-w-md bg-card p-6 sm:p-8 rounded-xl shadow-2xl">
      <div class="flex justify-center mb-4">
          <img src="/Brasao Color.png" alt="Logo" class="h-28 w-auto m-2 p-2" />
          <img src="/logo.png" alt="Logo" class="h-32 w-auto p-1" />
      </div>

      <h2 v-if="cardTitle" class="text-center text-3xl font-bold text-foreground mb-6">
        {{ cardTitle }}
      </h2>

      <slot />
    </div>
  </div>
</template>

<style scoped>
/* Estilos adicionais se necessário */
</style>