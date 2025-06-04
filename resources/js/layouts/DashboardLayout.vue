<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import { Link, router, Head, usePage } from '@inertiajs/vue3';

const page = usePage();

// Lógica da Sidebar
const sidebarOpen = ref(true);
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

// --- LÓGICA DO DROPDOWN DE TEMA ---
const isDropdownOpen = ref(false); // Para o dropdown de tema
const themeChoice = ref('system');
const themeSwitcherRef = ref<HTMLDivElement | null>(null); // Ref para o dropdown de tema

const toggleDropdown = () => { // Para o dropdown de tema
    isDropdownOpen.value = !isDropdownOpen.value;
};

const applyHtmlTheme = () => { /* ... sua função existente ... */
    let applyDark;
    if (themeChoice.value === 'light') applyDark = false;
    else if (themeChoice.value === 'dark') applyDark = true;
    else { // system
        applyDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    document.documentElement.classList.toggle('dark', applyDark);
};

const selectThemeChoice = (choice: string) => { /* ... sua função existente ... */
    themeChoice.value = choice;
    localStorage.setItem('themeUserChoice', choice);
    applyHtmlTheme();
    isDropdownOpen.value = false;
};

let osThemeMediaQuery: MediaQueryList | null = null;
const systemThemeChangeListener = () => { /* ... sua função existente ... */
    if (themeChoice.value === 'system') applyHtmlTheme();
};

const updateSystemThemeListener = () => { /* ... sua função existente ... */
    osThemeMediaQuery?.removeEventListener('change', systemThemeChangeListener);
    if (themeChoice.value === 'system') {
        osThemeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        osThemeMediaQuery.addEventListener('change', systemThemeChangeListener);
    }
};
// --- FIM DA LÓGICA PARA O DROPDOWN DE TEMA ---


// --- LÓGICA DO DROPDOWN DO USUÁRIO ---
const userDropdownOpen = ref(false);
const userDropdownRef = ref<HTMLDivElement | null>(null); // <<< ADICIONADO: Ref para o dropdown do usuário

const toggleUserDropdown = () => {
    userDropdownOpen.value = !userDropdownOpen.value;
};
const closeUserDropdown = () => {
    userDropdownOpen.value = false;
};
const logout = () => {
    router.post(route('logout'));
};
// --- FIM DA LÓGICA DO DROPDOWN DO USUÁRIO ---


// --- LÓGICA PARA CLICAR FORA ---
const handleClickOutsideThemeDropdown = (event: MouseEvent) => {
    if (themeSwitcherRef.value && !themeSwitcherRef.value.contains(event.target as Node)) {
        isDropdownOpen.value = false; // Fecha o dropdown de tema
    }
};

const handleClickOutsideUserDropdown = (event: MouseEvent) => {
    if (userDropdownRef.value && !userDropdownRef.value.contains(event.target as Node)) {
        closeUserDropdown(); // Fecha o dropdown do usuário
    }
};

// --- LIFECYCLE HOOKS ---
onMounted(() => {
    // Lógica de tema
    const savedChoice = localStorage.getItem('themeUserChoice');
    if (savedChoice && ['light', 'dark', 'system'].includes(savedChoice)) {
        themeChoice.value = savedChoice;
    } else {
        themeChoice.value = 'system';
    }
    applyHtmlTheme();
    updateSystemThemeListener();

    // Adiciona listeners para clique fora
    document.addEventListener('click', handleClickOutsideThemeDropdown);
    document.addEventListener('click', handleClickOutsideUserDropdown);
});

watch(themeChoice, () => { // Para tema
    updateSystemThemeListener();
});

onBeforeUnmount(() => {
    // Limpa listener de tema do SO
    osThemeMediaQuery?.removeEventListener('change', systemThemeChangeListener);

    // Remove listeners de clique fora
    document.removeEventListener('click', handleClickOutsideThemeDropdown);
    document.removeEventListener('click', handleClickOutsideUserDropdown);
});

</script>

<template>

    <Head title="Lagoa GED" />
    <div class="flex h-screen overflow-hidden bg-background text-foreground">
        <aside :class="[
            'fixed inset-y-0 left-0 z-30 flex flex-col transition-all duration-300 ease-in-out',
            'bg-sidebar text-sidebar-foreground shadow-lg border-r border-sidebar-border',
            // Largura: Dinâmica em desktop, fixa em mobile quando aberta
            sidebarOpen ? 'lg:w-64' : 'lg:w-20', // Largura para desktop (expandida vs mini)
            'w-64', // Largura para mobile quando o overlay está ativo

            // Translação: Controla visibilidade em mobile e garante posição em desktop
            // Em mobile (<lg): desliza para dentro/fora.
            // Em desktop (>=lg): fica sempre em translate-x-0 (fixa na esquerda).
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]">
            <div class="h-16 p-4 border-b border-sidebar-border flex items-center shrink-0"
                :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                <Link :href="route('dashboard')" class="flex items-center overflow-hidden"
                    :class="sidebarOpen ? 'space-x-3' : 'space-x-0'" title="Painel Principal">
                <img src="/logo.png" alt="Logo Lagoa GED" class="flex-shrink-0 object-contain"
                    :class="sidebarOpen ? 'h-8 w-auto' : 'h-9 w-9'">
                <span v-if="sidebarOpen" class="text-xl font-semibold text-sidebar-foreground whitespace-nowrap">
                    Lagoa GED
                </span>
                </Link>
            </div>

            <nav class="flex-1 overflow-y-auto py-4" :class="sidebarOpen ? 'px-4' : 'px-2'">
                <ul class="space-y-1">
                    <li>
                        <span v-if="sidebarOpen"
                            class="block px-3 py-2 text-xs font-semibold text-muted-foreground uppercase">
                            Navegação
                        </span>
                        <div v-else class="h-8 flex items-center justify-center my-1" title="Navegação">
                            <svg class="w-5 h-5 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                            </svg>
                        </div>
                    </li>
                    <li>
                        <Link :href="route('dashboard')" :title="!sidebarOpen ? 'Dashboard' : null" :class="[
                            'flex items-center p-3 rounded-md transition-colors duration-200 group',
                            sidebarOpen ? 'space-x-3' : 'justify-center',
                            route().current('dashboard') ? 'bg-cyan-600 text-sidebar-primary-foreground' : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                        ]">
                        <svg class="w-6 h-6 text-current flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-9v10a1 1 0 001 1h3M19 10l-2 2m0 0l-7 7-7-7">
                            </path>
                        </svg>
                        <span v-if="sidebarOpen" class="whitespace-nowrap">Dashboard</span>
                        </Link>
                    </li>
                    <!--li>
                        <span v-if="sidebarOpen"
                            class="block px-3 py-2 text-xs font-semibold text-muted-foreground uppercase mt-4">
                            UI Components
                        </span>
                        <div v-else class="h-8 flex items-center justify-center mt-4 my-1" title="UI Components">
                            <svg class="w-5 h-5 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.75 3.75H6.375a1.875 1.875 0 00-1.875 1.875v11.25a1.875 1.875 0 001.875 1.875h11.25a1.875 1.875 0 001.875-1.875V13.5m-4.125-9.375L16.5 3.75m0 0L19.125 1.125M16.5 3.75v4.875c0 .621-.504 1.125-1.125 1.125H10.5M16.5 3.75L10.5 9.75">
                                </path>
                            </svg>
                        </div>
                    </li>
                    <li>
                        <a href="#" :title="!sidebarOpen ? 'Color' : null" :class="[
                            'flex items-center p-3 rounded-md transition-colors duration-200 group',
                            sidebarOpen ? 'space-x-3' : 'justify-center',
                            'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                        ]">
                            <svg class="w-6 h-6 text-current flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0014.586 3H4a2 2 0 00-2 2v14a2 2 0 002 2z">
                                </path>
                            </svg>
                            <span v-if="sidebarOpen" class="whitespace-nowrap">Color</span>
                        </a>
                    </li!-->
                </ul>
            </nav>
        </aside>

        <div v-if="sidebarOpen" @click="toggleSidebar" class="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden">
        </div>

        <div class="flex-1 flex flex-col min-w-0 transition-all duration-300 ease-in-out" :class="{
            'lg:ml-64': sidebarOpen,    // Margem Desktop: sidebar expandida
            'lg:ml-20': !sidebarOpen,   // Margem Desktop: sidebar mini/recolhida
            // Em mobile, a sidebar é overlay, então a margem não é alterada aqui.
        }">
            <header
                class="h-16 bg-card text-card-foreground shadow-md flex items-center justify-between px-6 z-10 shrink-0">
                <button @click="toggleSidebar" title="Abrir/Fechar Menu"
                    class="p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-ring text-foreground"> <svg
                        class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <div class="hidden lg:flex items-center space-x-2 text-muted-foreground text-sm">
                    <span class="text-foreground font-semibold">Home</span>
                    <span>/</span>
                    <span>Dashboard</span>
                    <span>/</span>
                    <span class="text-primary">Default</span>
                </div>

                <div class="flex items-center space-x-4">
                    <div ref="themeSwitcherRef" class="relative">
                        <button @click="toggleDropdown" title="Alterar Tema"
                            class="p-2 rounded-md hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring text-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            </svg>
                        </button>
                        <div v-if="isDropdownOpen"
                            class="absolute right-0 mt-2 w-48 bg-card rounded-md shadow-lg py-1 z-50 border border-border">
                            <a @click.prevent="selectThemeChoice('light')" href="#"
                                :class="['flex items-center px-4 py-2 text-sm text-foreground hover:bg-muted', { 'bg-muted': themeChoice === 'light' }]"><svg
                                    class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                </svg>Claro</a>
                            <a @click.prevent="selectThemeChoice('dark')" href="#"
                                :class="['flex items-center px-4 py-2 text-sm text-foreground hover:bg-muted', { 'bg-muted': themeChoice === 'dark' }]"><svg
                                    class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                                </svg>Escuro</a>
                            <a @click.prevent="selectThemeChoice('system')" href="#"
                                :class="['flex items-center px-4 py-2 text-sm text-foreground hover:bg-muted', { 'bg-muted': themeChoice === 'system' }]"><svg
                                    class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.594 3.94c.09-.542.56-1.003 1.11-.952l2.27.265a1.5 1.5 0 011.487 1.134l.48 1.88a2.25 2.25 0 104.298-.002l.48-1.88a1.5 1.5 0 011.487-1.134l2.27-.265c.55-.051 1.02.41 1.11.952.03.196.03.398 0 .598l-.304 1.81A2.25 2.25 0 0021.75 7.5H17.5a2.25 2.25 0 00-2.25 2.25v1.5A2.25 2.25 0 0017.5 13.5h4.25a2.25 2.25 0 002.226-2.041l.305-1.81c.09-.54-.062-1.111-.444-1.515a2.25 2.25 0 00-.924-.728M19.5 9.75v.001M19.5 12v.001M19.5 14.25v.001M4.5 15.75A2.25 2.25 0 002.25 13.5H1.5A2.25 2.25 0 00-.75 11.25v-1.5A2.25 2.25 0 001.5 7.5H2.25A2.25 2.25 0 004.5 5.25V3.75c0-.966.784-1.75 1.75-1.75h1.5c.966 0 1.75.784 1.75 1.75v7.5c0 .966-.784 1.75-1.75 1.75h-1.5A1.75 1.75 0 014.5 15.75z" />
                                </svg>Sistema</a>
                        </div>
                    </div>

                    <div ref="userDropdownRef" class="relative">
                        <button @click="toggleUserDropdown" title="Menu do Usuário"
                            class="flex items-center justify-center w-10 h-10 p-1 rounded-full focus:outline-none focus:ring-2 focus:ring-ring text-foreground hover:bg-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-8 h-8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                        <div v-if="userDropdownOpen" 
                            class="absolute right-0 mt-2 w-max min-w-[12rem] bg-card border border-border rounded-md shadow-lg py-1 z-50">
                            <div
                                class="px-4 py-3 text-sm text-muted-foreground border-b border-border truncate w-full min-w-0">
                                {{ $page.props.auth.user.email }}
                            </div>
                            <Link :href="route('profile.edit')"
                                class="block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted"
                                @click="closeUserDropdown">
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.82 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.82 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.82-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.82-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>Configurações</span>
                            </div>
                            </Link>
                            <form @submit.prevent="logout">
                                <button type="submit"
                                    class="block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted"
                                    >
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                            </path>
                                        </svg>
                                        <span>Sair</span>
                                    </div>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-6 overflow-y-auto bg-background">
                <slot />
            </main>
        </div>
    </div>
</template>