<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import { Link, router, Head, usePage } from '@inertiajs/vue3';
import { CircleUserRoundIcon, File, House, LogOut, Menu, Minus, MoonIcon, Palette, Sun, SunMoon, Headset, MonitorCogIcon, Shield, FileCogIcon, UserCog2, X, Bell } from 'lucide-vue-next';
import type { BreadcrumbItemType } from '@/types';
import NotificationDropdown from '@/components/NotificationDropdown.vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();

const can = computed(() => {
    return (permission) => {
        const permissions = page.props.auth?.user?.permissions ?? {};
        return permissions[permission] ?? false;
    };
});

// Lógica da Sidebar
const sidebarOpen = ref(true);
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

// Função para fechar a sidebar em telas pequenas
const closeSidebarOnSmallScreens = () => {
    // Verifica se a tela é 'pequena' (menor que o breakpoint 'lg' do Tailwind, que é 1024px)
    if (window.innerWidth < 1024) {
        sidebarOpen.value = false;
    }
};

// --- LÓGICA DO DROPDOWN DE TEMA ---
const isDropdownOpen = ref(false);
const themeChoice = ref('system');
const themeSwitcherRef = ref<HTMLDivElement | null>(null);

const toggleDropdown = () => {
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

// --- LÓGICA PARA O SUBMENU DE PERMISSÕES ---
const permissionsSubmenuOpen = ref(false);
const togglePermissionsSubmenu = () => {
    if (!sidebarOpen.value) {
        // Se a sidebar estiver fechada e a tela for pequena, abre a sidebar primeiro
        sidebarOpen.value = true;
        // Pequeno atraso para a transição da sidebar terminar antes de abrir o submenu
        setTimeout(() => {
            permissionsSubmenuOpen.value = !permissionsSubmenuOpen.value;
        }, 300); // Ajuste este tempo se a transição da sidebar for mais longa
    } else {
        // Se a sidebar já estiver aberta, apenas alterna o submenu
        permissionsSubmenuOpen.value = !permissionsSubmenuOpen.value;
    }
};

// ** MODIFICAÇÃO CHAVE AQUI: Fechar sidebar e submenu após a navegação **
router.on('finish', () => {
    closeSidebarOnSmallScreens(); // Fechar a sidebar se for uma tela pequena
    permissionsSubmenuOpen.value = false; // Garante que o submenu de permissões feche em qualquer navegação
});

watch(sidebarOpen, (newValue) => {
    // Se a sidebar for fechada em telas grandes (desktop), feche o submenu
    if (!newValue && window.innerWidth >= 1024) {
        permissionsSubmenuOpen.value = false;
    }
});
// --- FIM DA LÓGICA PARA O SUBMENU DE PERMISSÕES ---

// --- LÓGICA PARA CLICAR FORA ---
const handleClickOutsideThemeDropdown = (event: MouseEvent) => {
    if (themeSwitcherRef.value && !themeSwitcherRef.value.contains(event.target as Node)) {
        isDropdownOpen.value = false;
    }
};

const permissionsMenuItemRef = ref<HTMLDivElement | null>(null);

const handleClickOutsideUserDropdown = (event: MouseEvent) => {
    if (userDropdownRef.value && !userDropdownRef.value.contains(event.target as Node)) {
        closeUserDropdown();
    }
};

const handleClickOutsidePermissionsSubmenu = (event: MouseEvent) => {
    if (permissionsMenuItemRef.value && !permissionsMenuItemRef.value.contains(event.target as Node)) {
        // Apenas feche se o clique não foi no item do menu e se o submenu está aberto
        if (permissionsSubmenuOpen.value && sidebarOpen.value) { // Verifica se sidebarOpen também é true
            permissionsSubmenuOpen.value = false;
        }
    }
};

// --- LIFECYCLE HOOKS ---
onMounted(() => {
    const savedChoice = localStorage.getItem('themeUserChoice');
    if (savedChoice && ['light', 'dark', 'system'].includes(savedChoice)) {
        themeChoice.value = savedChoice;
    } else {
        themeChoice.value = 'system';
    }
    applyHtmlTheme();
    updateSystemThemeListener();
    document.addEventListener('click', handleClickOutsideThemeDropdown);
    document.addEventListener('click', handleClickOutsideUserDropdown);
    document.addEventListener('click', handleClickOutsidePermissionsSubmenu);
});

watch(themeChoice, () => {
    updateSystemThemeListener();
});

onBeforeUnmount(() => {
    osThemeMediaQuery?.removeEventListener('change', systemThemeChangeListener);
    document.removeEventListener('click', handleClickOutsideThemeDropdown);
    document.removeEventListener('click', handleClickOutsideUserDropdown);
    document.removeEventListener('click', handleClickOutsidePermissionsSubmenu);
});

</script>

<template>

    <Head title="Lagoa GED" />
    <div class="flex h-screen overflow-hidden bg-gradient-to-br from-stone-50 via-zinc-50 to-slate-100 dark:from-zinc-900 dark:via-stone-900 dark:to-slate-950 text-foreground">
        <!-- Sidebar -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-30 flex flex-col transition-all duration-300 ease-in-out',
            'bg-white/80 dark:bg-zinc-900/90 backdrop-blur-xl text-sidebar-foreground shadow-2xl border-r border-stone-200/50 dark:border-zinc-700/50',
            sidebarOpen ? 'lg:w-64' : 'lg:w-28',
            'w-64',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]">
            <!-- Logo Section -->
            <div class="p-4 border-b border-stone-200/50 dark:border-zinc-700/50 flex items-center justify-center shrink-0 bg-gradient-to-r from-stone-600 to-zinc-700 dark:from-stone-800 dark:to-zinc-800">
                <Link :href="route('dashboard')" class="flex flex-col items-center overflow-hidden" title="Painel Principal">
                    <div class="flex items-center" :class="sidebarOpen ? 'space-x-2' : 'space-x-1'">
                        <img src="/Brasao Color.png" alt="Logo Lagoa GED" class="flex-shrink-0 object-contain drop-shadow-lg"
                            :class="sidebarOpen ? 'h-15 w-auto' : 'h-7 w-auto'">
                        <img src="/logo.png" alt="Logo Lagoa GED" class="flex-shrink-0 object-contain drop-shadow-lg"
                            :class="sidebarOpen ? 'h-15 w-auto' : 'h-7 w-auto'">
                    </div>
                    <span v-if="sidebarOpen" class="text-xl font-bold text-white whitespace-nowrap drop-shadow-md mt-2">
                        Lagoa GED
                    </span>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-6" :class="sidebarOpen ? 'px-4' : 'px-3'">
                <ul class="space-y-2">
                    <li>
                        <span v-if="sidebarOpen"
                            class="block px-3 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            Navegação
                        </span>
                        <div v-else class="h-6 flex items-center justify-center my-2" title="Navegação">
                            <Minus class="w-4 h-4 text-slate-400" />
                        </div>
                    </li>
                    
                    <!-- Dashboard -->
                    <li>
                        <Link :href="route('dashboard')" :title="!sidebarOpen ? 'Dashboard' : null" :class="[
                                'flex items-center px-3 py-3 rounded-xl transition-all duration-200 group relative overflow-hidden',
                                sidebarOpen ? 'space-x-3' : 'justify-center',
                                route().current('dashboard') 
                                    ? 'bg-gradient-to-r from-stone-500 to-zinc-600 text-white shadow-lg transform scale-[1.02]' 
                                    : 'text-slate-700 dark:text-slate-300 hover:bg-gradient-to-r hover:from-stone-50 hover:to-zinc-50 dark:hover:from-stone-900/20 dark:hover:to-zinc-900/20 hover:text-stone-700 dark:hover:text-stone-300'
                            ]">
                        <div :class="[
                            'p-2 rounded-lg transition-all duration-200',
                            route().current('dashboard') 
                                ? 'bg-white/20 shadow-lg' 
                                : 'group-hover:bg-stone-100 dark:group-hover:bg-stone-900/30'
                        ]">
                            <House :class="route().current('dashboard') ? 'w-5 h-5' : 'w-5 h-5'" />
                        </div>
                        <span v-if="sidebarOpen" class="font-medium whitespace-nowrap">Página Inicial</span>
                        </Link>
                    </li>

                    <!-- Documents -->
                    <li>
                        <Link :href="route('documents.index')" :title="!sidebarOpen ? 'Documentos' : null" :class="[
                                'flex items-center px-3 py-3 rounded-xl transition-all duration-200 group relative overflow-hidden',
                                sidebarOpen ? 'space-x-3' : 'justify-center',
                                route().current('documents.*') 
                                    ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg transform scale-[1.02]' 
                                    : 'text-slate-700 dark:text-slate-300 hover:bg-gradient-to-r hover:from-emerald-50 hover:to-teal-50 dark:hover:from-emerald-900/20 dark:hover:to-teal-900/20 hover:text-emerald-700 dark:hover:text-emerald-300'
                            ]">
                        <div :class="[
                            'p-2 rounded-lg transition-all duration-200',
                            route().current('documents.*') 
                                ? 'bg-white/20 shadow-lg' 
                                : 'group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/30'
                        ]">
                            <File :class="route().current('documents.*') ? 'w-5 h-5' : 'w-5 h-5'" />
                        </div>
                        <span v-if="sidebarOpen" class="font-medium whitespace-nowrap">Documentos</span>
                        </Link>
                    </li>

                    <!-- Permissions Menu -->
                    <li v-if="can('view_any_groups')" ref="permissionsMenuItemRef">
                        <button @click="togglePermissionsSubmenu" :title="!sidebarOpen ? 'Permissões' : undefined" :class="[
                                'flex items-center px-3 py-3 rounded-xl transition-all duration-200 w-full text-left group relative overflow-hidden',
                                sidebarOpen ? 'space-x-3' : 'justify-center',
                                permissionsSubmenuOpen || route().current('permissions.*') || route().current('users.*') || route().current('groups.*')
                                    ? 'bg-gradient-to-r from-purple-500 to-violet-600 text-white shadow-lg transform scale-[1.02]' 
                                    : 'text-slate-700 dark:text-slate-300 hover:bg-gradient-to-r hover:from-purple-50 hover:to-violet-50 dark:hover:from-purple-900/20 dark:hover:to-violet-900/20 hover:text-purple-700 dark:hover:text-purple-300'
                            ]">
                            <div :class="[
                                'p-2 rounded-lg transition-all duration-200',
                                permissionsSubmenuOpen || route().current('permissions.*') || route().current('users.*') || route().current('groups.*')
                                    ? 'bg-white/20 shadow-lg' 
                                    : 'group-hover:bg-purple-100 dark:group-hover:bg-purple-900/30'
                            ]">
                                <MonitorCogIcon class="w-5 h-5" />
                            </div>
                            <span v-if="sidebarOpen" class="font-medium whitespace-nowrap flex-grow">Permissões</span>
                            <svg v-if="sidebarOpen"
                                :class="['w-4 h-4 transition-transform duration-300', { 'rotate-90': permissionsSubmenuOpen }]"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        
                        <!-- Submenu -->
                        <div v-if="permissionsSubmenuOpen && sidebarOpen" class="ml-6 mt-2 space-y-1 border-l-2 border-purple-200 dark:border-purple-700 pl-4">
                            <Link :href="route('groups.index')"
                                class="flex items-center px-3 py-2 rounded-lg transition-all duration-200 group text-sm"
                                :class="[route().current('groups.*') 
                                    ? 'bg-gradient-to-r from-purple-100 to-violet-100 dark:from-purple-900/40 dark:to-violet-900/40 text-purple-700 dark:text-purple-300 font-medium' 
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:text-purple-600 dark:hover:text-purple-300']">
                            <Shield class="w-4 h-4 mr-3" />
                            <span>Grupos</span>
                            </Link>
                            <Link :href="route('users.index')"
                                class="flex items-center px-3 py-2 rounded-lg transition-all duration-200 group text-sm"
                                :class="[route().current('users.*') 
                                    ? 'bg-gradient-to-r from-purple-100 to-violet-100 dark:from-purple-900/40 dark:to-violet-900/40 text-purple-700 dark:text-purple-300 font-medium' 
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:text-purple-600 dark:hover:text-purple-300']">
                            <UserCog2 class="w-4 h-4 mr-3" />
                            <span>Usuários</span>
                            </Link>
                            <Link :href="route('documents.batch-permissions')"
                                class="flex items-center px-3 py-2 rounded-lg transition-all duration-200 group text-sm"
                                :class="[route().current('documents.batch-permissions') 
                                    ? 'bg-gradient-to-r from-purple-100 to-violet-100 dark:from-purple-900/40 dark:to-violet-900/40 text-purple-700 dark:text-purple-300 font-medium' 
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:text-purple-600 dark:hover:text-purple-300']">
                            <FileCogIcon class="w-4 h-4 mr-3" />
                            <span>Documentos</span>
                            </Link>
                        </div>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Overlay for mobile -->
        <div v-if="sidebarOpen" @click="toggleSidebar" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-20 lg:hidden transition-opacity duration-300">
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 transition-all duration-300 ease-in-out" :class="{
                'lg:ml-64': sidebarOpen,
                'lg:ml-28': !sidebarOpen,
            }">
            <!-- Header -->
            <header class="h-16 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl text-card-foreground shadow-lg border-b border-stone-200/50 dark:border-zinc-700/50 flex items-center justify-between px-6 z-10 shrink-0">
                <!-- Left side -->
                <div class="flex items-center gap-4">
                    <button @click="toggleSidebar" title="Abrir/Fechar Menu"
                        class="p-2 rounded-lg hover:bg-stone-100 dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-stone-500 text-foreground transition-colors duration-200">
                        <Menu class="w-5 h-5" />
                    </button>

                    <!-- Breadcrumbs -->
                    <div v-if="props.breadcrumbs.length > 0"
                        class="hidden lg:flex items-center space-x-2 text-sm">
                        <template v-for="(crumb, index) in props.breadcrumbs" :key="index">
                            <Link v-if="index < props.breadcrumbs.length - 1 && crumb.href" :href="crumb.href"
                                class="text-stone-500 hover:text-stone-600 dark:text-stone-400 dark:hover:text-stone-300 transition-colors duration-200 font-medium">
                            {{ crumb.title }}
                            </Link>
                            <span v-else class="text-stone-900 dark:text-stone-100 font-semibold">{{ crumb.title }}</span>

                            <span v-if="index < props.breadcrumbs.length - 1" class="text-stone-300 dark:text-stone-600">/</span>
                        </template>
                    </div>
                    <div v-else class="hidden lg:flex items-center text-sm">
                        <Link :href="route('dashboard')" class="text-stone-900 dark:text-stone-100 font-semibold">Página Inicial</Link>
                    </div>
                </div>

                <!-- Right side -->
                <div class="flex items-center space-x-3">
                    <!-- Notifications -->
                    <div class="relative">
                        <NotificationDropdown />
                    </div>

                    <!-- Theme Switcher -->
                    <div ref="themeSwitcherRef" class="relative">
                        <button @click="toggleDropdown" title="Alterar Tema"
                            class="p-2 rounded-lg hover:bg-stone-100 dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-stone-500 text-foreground transition-all duration-200">
                            <Palette class="w-5 h-5" />
                        </button>
                        <div v-if="isDropdownOpen"
                            class="absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-800 rounded-xl shadow-2xl py-2 z-50 border border-stone-200 dark:border-zinc-700 backdrop-blur-xl">
                            <a @click.prevent="selectThemeChoice('light')" href="#"
                                :class="[
                                    'flex items-center px-4 py-3 text-sm text-foreground hover:bg-stone-50 dark:hover:bg-zinc-700/50 transition-colors duration-200',
                                    { 'bg-stone-50 dark:bg-zinc-900/20 text-stone-600 dark:text-stone-400': themeChoice === 'light' }
                                ]">
                                <Sun class="w-5 h-5 mr-3" />
                                <span class="font-medium">Claro</span>
                            </a>
                            <a @click.prevent="selectThemeChoice('dark')" href="#"
                                :class="[
                                    'flex items-center px-4 py-3 text-sm text-foreground hover:bg-stone-50 dark:hover:bg-zinc-700/50 transition-colors duration-200',
                                    { 'bg-stone-50 dark:bg-zinc-900/20 text-stone-600 dark:text-stone-400': themeChoice === 'dark' }
                                ]">
                                <MoonIcon class="w-5 h-5 mr-3" />
                                <span class="font-medium">Escuro</span>
                            </a>
                            <a @click.prevent="selectThemeChoice('system')" href="#"
                                :class="[
                                    'flex items-center px-4 py-3 text-sm text-foreground hover:bg-stone-50 dark:hover:bg-zinc-700/50 transition-colors duration-200',
                                    { 'bg-stone-50 dark:bg-zinc-900/20 text-stone-600 dark:text-stone-400': themeChoice === 'system' }
                                ]">
                                <SunMoon class="w-5 h-5 mr-3" />
                                <span class="font-medium">Sistema</span>
                            </a>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div ref="userDropdownRef" class="relative">
                        <button @click="toggleUserDropdown" title="Menu do Usuário"
                            class="flex items-center justify-center w-10 h-10 rounded-lg hover:bg-stone-100 dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-stone-500 text-foreground transition-all duration-200">
                            <CircleUserRoundIcon class="w-6 h-6" />
                        </button>
                        <div v-if="userDropdownOpen"
                            class="absolute right-0 mt-2 w-56 bg-white dark:bg-zinc-800 border border-stone-200 dark:border-zinc-700 rounded-xl shadow-2xl py-2 z-50 backdrop-blur-xl">
                            <div class="px-4 py-3 text-sm text-stone-600 dark:text-stone-400 border-b border-stone-100 dark:border-zinc-700 truncate">
                                {{ (page.props.auth as any)?.user?.email }}
                            </div>
                            <a href="https://glpi.lagoasanta.mg.gov.br"
                                class="flex items-center w-full px-4 py-3 text-sm text-foreground hover:bg-stone-50 dark:hover:bg-zinc-700/50 transition-colors duration-200"
                                @click="closeUserDropdown" target="_blank">
                                <Headset class="w-5 h-5 mr-3 text-stone-500" />
                                <span class="font-medium">Suporte</span>
                            </a>
                            <form @submit.prevent="logout">
                                <button type="submit"
                                    class="flex items-center w-full px-4 py-3 text-sm text-foreground hover:bg-stone-50 dark:hover:bg-zinc-700/50 transition-colors duration-200">
                                    <LogOut class="w-5 h-5 mr-3 text-red-500" />
                                    <span class="font-medium">Sair</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-gradient-to-br from-stone-50 via-zinc-50/30 to-slate-100/30 dark:from-zinc-900 dark:via-stone-900/70 dark:to-slate-950/30">
                <div class="min-h-full flex flex-col">
                    <div class="flex-1">
                        <slot />
                    </div>
                    
                    <!-- Rodapé -->
                    <footer class="mt-auto py-6 px-4 sm:px-6 lg:px-8 border-t border-gray-200 dark:border-zinc-700 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm">
                        <div class="container mx-auto text-center">
                            <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                                Copyright © 2025 Prefeitura Municipal de Lagoa Santa
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                Projetado pela Diretoria de Inovação Tecnológica
                            </p>
                        </div>
                    </footer>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Animações e efeitos modernos */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes float {
    0%, 100% { 
        transform: translateY(0px); 
    }
    50% { 
        transform: translateY(-2px); 
    }
}

/* Efeitos de hover suaves */
.group:hover {
    animation: float 2s ease-in-out infinite;
}

/* Glassmorphism effect */
.backdrop-blur-xl {
    backdrop-filter: blur(16px);
}

/* Smooth transitions para os dropdowns */
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.3s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Scrollbar personalizada */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(156, 163, 175, 0.3);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(156, 163, 175, 0.5);
}

/* Dark mode scrollbar */
.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(75, 85, 99, 0.3);
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(75, 85, 99, 0.5);
}
</style>