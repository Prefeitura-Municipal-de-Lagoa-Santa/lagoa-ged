<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, } from 'vue';
import { Link, router, Head, usePage } from '@inertiajs/vue3';
import { CircleUserRoundIcon, File, House, LogOut, Menu, Minus, MoonIcon, Palette, Settings2, Sun, SunMoon, } from 'lucide-vue-next';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [], // Garante que props.breadcrumbs seja sempre um array
});

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

const applyHtmlTheme = () => {
    let applyDark;
    if (themeChoice.value === 'light') applyDark = false;
    else if (themeChoice.value === 'dark') applyDark = true;
    else { // system
        applyDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    document.documentElement.classList.toggle('dark', applyDark);
};

const selectThemeChoice = (choice: string) => {
    themeChoice.value = choice;
    localStorage.setItem('themeUserChoice', choice);
    applyHtmlTheme();
    isDropdownOpen.value = false;
};

let osThemeMediaQuery: MediaQueryList | null = null;
const systemThemeChangeListener = () => {
    if (themeChoice.value === 'system') applyHtmlTheme();
};

const updateSystemThemeListener = () => {
    osThemeMediaQuery?.removeEventListener('change', systemThemeChangeListener);
    if (themeChoice.value === 'system') {
        osThemeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        osThemeMediaQuery.addEventListener('change', systemThemeChangeListener);
    }
};
// --- FIM DA LÓGICA PARA O DROPDOWN DE TEMA ---


// --- LÓGICA DO DROPDOWN DO USUÁRIO ---
const userDropdownOpen = ref(false);
const userDropdownRef = ref<HTMLDivElement | null>(null);

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
                            <Minus />
                        </div>
                    </li>
                    <li>
                        <Link :href="route('dashboard')" :title="!sidebarOpen ? 'Dashboard' : null" :class="[
                            'flex items-center p-3 rounded-md transition-colors duration-200 group',
                            sidebarOpen ? 'space-x-3' : 'justify-center',
                            route().current('dashboard') ? 'bg-cyan-600 text-sidebar-primary-foreground' : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                        ]">
                        <House />
                        <span v-if="sidebarOpen" class="whitespace-nowrap">Página Inicial</span>
                        </Link>
                    </li>
                    <li>
                        <Link :href="route('documents.index')" :title="!sidebarOpen ? 'Documentos' : null" :class="[
                            'flex items-center p-3 rounded-md transition-colors duration-200 group',
                            sidebarOpen ? 'space-x-3' : 'justify-center',
                            route().current('documents.index') ? 'bg-cyan-600 text-sidebar-primary-foreground' : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                        ]">
                        <File />
                        <span v-if="sidebarOpen" class="whitespace-nowrap">Documentos</span>
                        </Link>
                    </li>
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
                    class="p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-ring text-foreground">
                    <Menu />
                </button>

                <div v-if="props.breadcrumbs.length > 0"
                    class="hidden lg:flex items-center space-x-1 text-sm text-muted-foreground">
                    <template v-for="(crumb, index) in props.breadcrumbs" :key="index"> 
                        <Link v-if="index < props.breadcrumbs.length - 1" :href="crumb.href"
                            class="hover:text-foreground transition-colors">
                        {{ crumb.title }}
                        </Link>
                        <span v-else class="text-foreground font-semibold">{{ crumb.title }}</span>

                        <span v-if="index < props.breadcrumbs.length - 1" class="mx-1">/</span>
                    </template>
                </div>
                <div v-else class="hidden lg:flex items-center text-sm">
                    <Link :href="route('dashboard')" class="text-foreground font-semibold">Página Inicial</Link>
                </div>

                <div class="flex items-center space-x-4">
                    <div ref="themeSwitcherRef" class="relative">
                        <button @click="toggleDropdown" title="Alterar Tema"
                            class="p-2 rounded-md hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring text-foreground">
                            <Palette />
                        </button>
                        <div v-if="isDropdownOpen"
                            class="absolute right-0 mt-2 w-48 bg-card rounded-md shadow-lg py-1 z-50 border border-border">
                            <a @click.prevent="selectThemeChoice('light')" href="#"
                                :class="['flex items-center px-4 py-2 text-sm text-foreground hover:bg-muted', { 'bg-muted': themeChoice === 'light' }]">
                                <Sun class="w-5 h-5 mr-2" />Claro
                            </a>
                            <a @click.prevent="selectThemeChoice('dark')" href="#"
                                :class="['flex items-center px-4 py-2 text-sm text-foreground hover:bg-muted', { 'bg-muted': themeChoice === 'dark' }]">
                                <MoonIcon class="w-5 h-5 mr-2" />Escuro
                            </a>
                            <a @click.prevent="selectThemeChoice('system')" href="#"
                                :class="['flex items-center px-4 py-2 text-sm text-foreground hover:bg-muted', { 'bg-muted': themeChoice === 'system' }]">
                                <SunMoon class="w-5 h-5 mr-2" /><span>Sistema</span>
                            </a>
                        </div>
                    </div>

                    <div ref="userDropdownRef" class="relative">
                        <button @click="toggleUserDropdown" title="Menu do Usuário"
                            class="flex items-center justify-center w-10 h-10 p-1 rounded-full focus:outline-none focus:ring-2 focus:ring-ring text-foreground hover:bg-muted">
                            <CircleUserRoundIcon />
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
                                <Settings2 />
                                <span>Configurações</span>
                            </div>
                            </Link>
                            <form @submit.prevent="logout">
                                <button type="submit"
                                    class="block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted">
                                    <div class="flex items-center space-x-2">
                                        <LogOut />
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