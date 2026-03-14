<template>
    <div class="flex h-screen bg-gray-50 dark:bg-gray-800">

        <!-- Sidebar -->
        <aside :class="[
            'flex flex-col bg-white dark:bg-gray-900 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 transition-all duration-300',
            collapsed ? 'w-16' : 'w-64'
        ]">
            <!-- Logo -->
            <div class="flex items-center justify-between px-4 py-5 border-b border-gray-200 dark:border-gray-700">
                <span v-if="!collapsed" class="text-xl font-bold text-indigo-600">HabitFlow</span>
                <button @click="collapsed = !collapsed"
                        class="p-1.5 rounded-lg hover:bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 dark:text-gray-500">
                    <ChevronLeft v-if="!collapsed" class="w-5 h-5" />
                    <ChevronRight v-else class="w-5 h-5" />
                </button>
            </div>
            <div class="px-2 pt-2 pb-1">
                <button @click="searchOpen = true"
                        :class="[
                'flex items-center gap-3 w-full px-3 py-2.5 rounded-lg transition-colors',
                'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'
            ]">
                    <Search class="w-5 h-5 shrink-0" />
                    <span v-if="!collapsed" class="text-sm font-medium text-gray-400 dark:text-gray-500">
                Search...
            </span>
                    <kbd v-if="!collapsed"
                         class="ml-auto text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-400 rounded border border-gray-200 dark:border-gray-600">
                        {{ searchShortcut }}
                    </kbd>
                </button>
            </div>
            <!-- Navigation -->
            <nav class="flex-1 px-2 py-4 space-y-1">
                <Link v-for="item in navItems" :key="item.href"
                      :href="item.href"
                      :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors duration-200 group',
                        isActive(item.href)
                            ? 'bg-indigo-50 text-indigo-600'
                            : 'text-gray-600 hover:bg-gray-100 dark:bg-gray-700 hover:text-gray-900 dark:text-gray-100'
                    ]">
                    <component :is="item.icon" class="w-5 h-5 shrink-0" />
                    <span v-if="!collapsed" class="text-sm font-medium flex-1">{{ item.label }}</span>
                    <!-- Badge for friends -->
                    <span v-if="item.badge && item.badge > 0 && !collapsed"
                          class="text-xs bg-indigo-500 text-white px-1.5 py-0.5 rounded-full min-w-[18px] text-center">
                             {{ item.badge }}
                    </span>
                </Link>
            </nav>

            <!-- User Profile Bottom -->
            <div class="border-t border-gray-200 dark:border-gray-700 p-3">
                <Link :href="route('profile.show')"
                      class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-100 dark:bg-gray-700 transition-colors">
                    <img :src="$page.props.auth.user.profile_photo_url"
                         :alt="$page.props.auth.user.name"
                         class="w-8 h-8 rounded-full object-cover shrink-0" />
                    <div v-if="!collapsed" class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                            {{ $page.props.auth.user.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 truncate">
                            {{ $page.props.auth.user.email }}
                        </p>
                    </div>
                </Link>

                <XpBar :xp="$page.props.xp_progress" />



                <!-- Logout -->
                <button @click="logout"
                        :class="[
                        'flex items-center gap-3 px-3 py-2 mt-1 w-full rounded-lg text-red-500 hover:bg-red-50 transition-colors',
                    ]">
                    <LogOut class="w-5 h-5 shrink-0" />
                    <span v-if="!collapsed" class="text-sm font-medium">Logout</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Top Header -->
            <header class="bg-white dark:bg-gray-900 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h1>
                    <p v-if="subtitle" class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ subtitle }}</p>
                </div>
                <slot name="header-actions" />
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>

        </div>
        <GlobalSearch v-model:open="searchOpen" />

        <ShortcutsModal v-model:open="shortcutsOpen" />
        <Toaster
            position="top-right"
            closeButton
            :duration="4000"
            :visibleToasts="5"
            offset="20px"
        />
    </div>
</template>

<script setup>
import {computed, ref} from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    LayoutDashboard,
    ListChecks,
    PlusCircle,
    Tag,
    Settings,
    ChevronLeft,
    ChevronRight,
    LogOut,
    BarChart2,
    LayoutTemplate,
    Users,
    Timer
} from 'lucide-vue-next'
import {Toaster} from "vue-sonner";
import { useToast } from '@/composables/useToast'
import {useTheme} from "@/composables/useTheme.js";
import ShortcutsModal from "@/Components/ShortcutsModal.vue";
import {useShortcuts} from "@/composables/useShortcuts.js";
import GlobalSearch from "@/Components/GlobalSearch.vue";
import { Search } from 'lucide-vue-next'
import XpBar from "@/Components/XpBar.vue";
import { useRealtime } from '@/composables/useRealtime'

const props = defineProps({
    title: String,
    subtitle: String,
})

const collapsed = ref(false)
const page = usePage()
const searchOpen    = ref(false)
const shortcutsOpen = ref(false)




const toasterTheme = computed(() => {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light'
})
const navItems = [
    { label: 'Dashboard',   href: route('dashboard'),   icon: LayoutDashboard },
    { label: 'My Habits',   href: route('habits.index'), icon: ListChecks },
    { label: 'New Habit',   href: route('habits.create'), icon: PlusCircle },
    { label: 'Templates',  href: route('templates.index'),  icon: LayoutTemplate },
    { label: 'Analytics',  href: route('analytics'),        icon: BarChart2 },
    { label: 'Categories',  href: route('categories.index'), icon: Tag },
    { label: 'Friends', href: route('friends.index'), icon: Users },
    { label: 'Settings',    href: route('settings'),     icon: Settings },
    { label: 'Pomodoro', href: route('pomodoro'), icon: Timer },

]

const isActive = (href) => page.url.startsWith(new URL(href).pathname)

const logout = () => router.post(route('logout'))

useTheme()
const toast = useToast()

const searchShortcut = computed(() =>
    page.props.auth?.user?.settings?.shortcuts?.search ?? '/'
)

useShortcuts({
    onHelp:   () => shortcutsOpen.value = true,
    onEscape: () => {
        searchOpen.value    = false
        shortcutsOpen.value = false
    },
    onSearch: () => searchOpen.value = true,  // ← add this
})
useShortcuts({
    onHelp:   () => shortcutsOpen.value = true,
    onEscape: () => shortcutsOpen.value = false,
})

const { on } = useRealtime()

// Make page.props the single source of truth so Inertia navigations and Echo events both update the same object
on('onXpAwarded', (e) => {
    if (e.xp_progress) {
        // Mutating Inertia page props directly is the recommended way to handle 
        // global state updates in Inertia Vue3 so all components see it
        page.props.xp_progress = { ...page.props.xp_progress, ...e.xp_progress }
    }
})

</script>
