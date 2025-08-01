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
    <div class="flex h-screen overflow-hidden bg-background text-foreground">
        <aside :class="[
            'fixed inset-y-0 left-0 z-30 flex flex-col transition-all duration-300 ease-in-out',
            'bg-sidebar text-sidebar-foreground shadow-lg border-r border-sidebar-border',
            sidebarOpen ? 'lg:w-55' : 'lg:w-28',
            'w-64',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]">
            <div class="p-3 border-b border-sidebar-border flex items-center justify-center shrink-0"
                :class="sidebarOpen ? 'justify-between' : 'justify-center'">
                <Link :href="route('dashboard')" class="flex flex-col items-center overflow-hidden"
                    :class="sidebarOpen ? 'space-x-3' : 'space-x-0'" title="Painel Principal">
                <div class="flex items-center">
                    <img src="/Brasao Color.png" alt="Logo Lagoa GED" class="flex-shrink-0 object-contain"
                        :class="sidebarOpen ? 'h-15 w-auto' : 'h-9 w-auto'">
                    <img src="/logo.png" alt="Logo Lagoa GED" class="flex-shrink-0 object-contain"
                        :class="sidebarOpen ? 'h-15 w-auto' : 'h-9 w-auto'">
                </div>
                <span v-if="sidebarOpen" class="text-2xl font-semibold text-sidebar-foreground whitespace-nowrap">
                    Lagoa GED
                </span>
                </Link>
            </div>

            <nav class="flex-1 overflow-y-auto py-4" :class="sidebarOpen ? 'px-5' : 'px-6'">
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

                    <li v-if="can('view_any_groups')" ref="permissionsMenuItemRef">
                        <button @click="togglePermissionsSubmenu" :title="!sidebarOpen ? 'Permissões' : undefined" :class="[
                                'flex items-center p-3 rounded-md transition-colors duration-200 w-full text-left group',
                                sidebarOpen ? 'space-x-3' : 'justify-center',
                                permissionsSubmenuOpen || route().current('permissions.*') || route().current('roles.*') ? 'bg-cyan-600 text-sidebar-primary-foreground' : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                            ]">
                            <MonitorCogIcon />
                            <span v-if="sidebarOpen" class="whitespace-nowrap flex-grow">Permissões</span>
                            <svg v-if="sidebarOpen"
                                :class="['w-4 h-4 transition-transform duration-200', { 'rotate-90': permissionsSubmenuOpen }]"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                        <div v-if="permissionsSubmenuOpen && sidebarOpen" class="ml-4 mt-1 space-y-1">
                            <Link :href="route('groups.index')"
                                class="flex items-center p-2 rounded-md transition-colors duration-200 group text-sm"
                                :class="[route().current('groups.index') ? 'bg-cyan-700 text-sidebar-primary-foreground' : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground']">
                            <Shield class="w-4 h-4 mr-2" />
                            <span>Grupos</span>
                            </Link>
                            <Link :href="route('users.index')"
                                class="flex items-center p-2 rounded-md transition-colors duration-200 group text-sm"
                                :class="[route().current('users.index') ? 'bg-cyan-700 text-sidebar-primary-foreground' : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground']">
                            <UserCog2 class="w-4 h-4 mr-2" />
                            <span>Usuários</span>
                            </Link>
                            <Link :href="route('documents.batch-permissions')"
                                class="flex items-center p-2 rounded-md transition-colors duration-200 group text-sm"
                                :class="[route().current('documents.batch-permissions') ? 'bg-cyan-700 text-sidebar-primary-foreground' : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground']">
                            <FileCogIcon class="w-4 h-4 mr-2" />
                            <span>Documentos</span>
                            </Link>
                        </div>
                    </li>
                </ul>
            </nav>
        </aside>

        <div v-if="sidebarOpen" @click="toggleSidebar" class="fixed inset-0 bg-opacity-25 z-20 lg:hidden">
        </div>

        <div class="flex-1 flex flex-col min-w-0 transition-all duration-300 ease-in-out" :class="{
                'lg:ml-55': sidebarOpen,
                'lg:ml-28': !sidebarOpen,
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
                        <Link v-if="index < props.breadcrumbs.length - 1 && crumb.href" :href="crumb.href"
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
                    <!-- Sistema de Notificações Avançado -->
                    <NotificationDropdown />

                    <!-- Botão do Tema -->
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
                                {{ (page.props.auth as any)?.user?.email }}
                            </div>
                            <a href="https://glpi.lagoasanta.mg.gov.br"
                                class="block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-muted"
                                @click="closeUserDropdown" target="_blank">
                                <div class="flex items-center space-x-2">
                                    <Headset />
                                    <span>Suporte</span>
                                </div>
                            </a>
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

            <main class="flex-1 p-6 overflow-y-auto bg-background dark:bg-neutral-800">
                <!--div v-if="successMessage"
                    class="w-fit max-w-lg mx-auto mb-4 rounded-full bg-green-100 px-6 py-2 text-sm font-medium text-green-800 shadow-lg">
                    {{ successMessage }}
                </div>
                <div v-if="errorMessage"
                    class="w-fit max-w-lg mx-auto mb-4 rounded-full bg-red-100 px-6 py-2 text-sm font-medium text-red-800 shadow-lg">
                    {{ errorMessage }}
                </div-->

                <slot />
            </main>
        </div>
    </div>
</template>