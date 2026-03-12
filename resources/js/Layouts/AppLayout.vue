<template>
    <div class="flex h-screen bg-gray-50">

        <!-- Sidebar -->
        <aside :class="[
            'flex flex-col bg-white border-r border-gray-200 transition-all duration-300',
            collapsed ? 'w-16' : 'w-64'
        ]">
            <!-- Logo -->
            <div class="flex items-center justify-between px-4 py-5 border-b border-gray-200">
                <span v-if="!collapsed" class="text-xl font-bold text-indigo-600">HabitFlow</span>
                <button @click="collapsed = !collapsed"
                        class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500">
                    <ChevronLeft v-if="!collapsed" class="w-5 h-5" />
                    <ChevronRight v-else class="w-5 h-5" />
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
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                    ]">
                    <component :is="item.icon" class="w-5 h-5 shrink-0" />
                    <span v-if="!collapsed" class="text-sm font-medium">{{ item.label }}</span>
                </Link>
            </nav>

            <!-- User Profile Bottom -->
            <div class="border-t border-gray-200 p-3">
                <Link :href="route('profile.show')"
                      class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <img :src="$page.props.auth.user.profile_photo_url"
                         :alt="$page.props.auth.user.name"
                         class="w-8 h-8 rounded-full object-cover shrink-0" />
                    <div v-if="!collapsed" class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ $page.props.auth.user.name }}
                        </p>
                        <p class="text-xs text-gray-500 truncate">
                            {{ $page.props.auth.user.email }}
                        </p>
                    </div>
                </Link>

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
            <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-900">{{ title }}</h1>
                    <p v-if="subtitle" class="text-sm text-gray-500">{{ subtitle }}</p>
                </div>
                <slot name="header-actions" />
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>

        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    LayoutDashboard,
    ListChecks,
    PlusCircle,
    Tag,
    Settings,
    ChevronLeft,
    ChevronRight,
    LogOut
} from 'lucide-vue-next'

const props = defineProps({
    title: String,
    subtitle: String,
})

const collapsed = ref(false)
const page = usePage()

const navItems = [
    { label: 'Dashboard',   href: route('dashboard'),   icon: LayoutDashboard },
    { label: 'My Habits',   href: route('habits.index'), icon: ListChecks },
    { label: 'New Habit',   href: route('habits.create'), icon: PlusCircle },
    { label: 'Categories',  href: route('categories.index'), icon: Tag },
    { label: 'Settings',    href: route('settings'),     icon: Settings },
]

const isActive = (href) => page.url.startsWith(new URL(href).pathname)

const logout = () => router.post(route('logout'))
</script>
