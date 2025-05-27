<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
  title?: string;
  description?: string;
}>();
</script>

<template>
  <div class="relative min-h-screen flex items-center justify-center bg-muted px-4 py-8">
    <!-- Bolhas decorativas -->
    <span class="absolute top-[calc(50%-250px)] right-[calc(50%-250px)] w-[200px] h-[200px] bg-blue-100 rounded-full opacity-40 animate-[float_7s_infinite]"></span>
    <span class="absolute top-[calc(50%-100px)] left-[calc(50%-200px)] w-5 h-5 bg-blue-300 rounded-full opacity-70 animate-[float_9s_infinite]"></span>
    <span class="absolute bottom-[calc(50%-100px)] right-[calc(50%-200px)] w-5 h-5 bg-blue-100 rounded-full opacity-60 animate-[float_6s_infinite]"></span>
    <span class="absolute bottom-[calc(50%-250px)] left-[calc(50%-250px)] w-[250px] h-[250px] bg-blue-200 rounded-full opacity-30 animate-[float_8s_infinite]"></span>

    <!-- Card de login -->
    <div class="relative z-10 w-full max-w-sm bg-white dark:bg-background border border-border rounded-2xl shadow-xl p-8">
      <div class="flex flex-col items-center mb-6">
        <Link :href="route('dashboard')" class="mb-4">
          <img src="/assets/images/logo.png" alt="Logo" class="h-30" />
        </Link>
        <h1 class="text-xl font-semibold text-center text-foreground">{{ title }}</h1>
        <p class="mt-1 text-sm text-muted-foreground text-center">{{ description }}</p>
      </div>

      <slot />
    </div>
  </div>
</template>

<style scoped>
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}
.animate-\[float_7s_infinite\] {
  animation: float 7s ease-in-out infinite;
}
.animate-\[float_9s_infinite\] {
  animation: float 9s ease-in-out infinite;
}
.animate-\[float_6s_infinite\] {
  animation: float 6s ease-in-out infinite;
}
.animate-\[float_8s_infinite\] {
  animation: float 8s ease-in-out infinite;
}
</style>
