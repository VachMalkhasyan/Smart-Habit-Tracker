<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    status: {
        type: Number,
        required: true,
    },
})

const config = computed(() => {
    const map = {
        403: {
            emoji: '🔒',
            title: 'Access Denied',
            message: "You don't have permission to view this page.",
            actions: [{ label: 'Go to Dashboard', href: '/dashboard', type: 'link' }],
        },
        404: {
            emoji: '🔍',
            title: 'Page Not Found',
            message: "The page you're looking for doesn't exist or was moved.",
            actions: [{ label: 'Go to Dashboard', href: '/dashboard', type: 'link' }],
        },
        419: {
            emoji: '⏱️',
            title: 'Session Expired',
            message: 'Your session expired. Please log in again.',
            actions: [{ label: 'Log In', href: '/login', type: 'link' }],
        },
        500: {
            emoji: '⚙️',
            title: 'Server Error',
            message: "Something went wrong on our end. We're on it!",
            actions: [
                { label: 'Try Again', type: 'reload' },
                { label: 'Go to Dashboard', href: '/dashboard', type: 'link', secondary: true },
            ],
        },
        503: {
            emoji: '🛠️',
            title: 'Under Maintenance',
            message: "We're doing some maintenance. Back shortly!",
            actions: [],
        },
    }
    return map[props.status] ?? {
        emoji: '❓',
        title: 'Something Went Wrong',
        message: 'An unexpected error occurred.',
        actions: [{ label: 'Go to Dashboard', href: '/dashboard', type: 'link' }],
    }
})
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex items-center justify-center px-4 relative overflow-hidden">

        <!-- Subtle background blobs -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-indigo-100 dark:bg-indigo-900/20 rounded-full blur-3xl opacity-40 pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-purple-100 dark:bg-purple-900/20 rounded-full blur-3xl opacity-40 pointer-events-none"></div>

        <div class="relative text-center max-w-md w-full">

            <!-- Watermark status code -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none"
                 aria-hidden="true">
                <span class="text-[180px] sm:text-[220px] font-black text-gray-900 dark:text-white opacity-[0.04] leading-none">
                    {{ status }}
                </span>
            </div>

            <!-- Floating emoji -->
            <div class="relative inline-block mb-6 animate-float">
                <span class="text-8xl drop-shadow-lg">{{ config.emoji }}</span>
            </div>

            <!-- Title -->
            <h1 class="text-3xl font-extrabold text-gray-800 dark:text-white mb-3 tracking-tight">
                {{ config.title }}
            </h1>

            <!-- Message -->
            <p class="text-gray-500 dark:text-gray-400 text-base leading-relaxed mb-8">
                {{ config.message }}
            </p>

            <!-- Action buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <template v-for="action in config.actions" :key="action.label">
                    <a v-if="action.type === 'reload'"
                        href="#"
                        @click.prevent="window.location.reload()"
                        :class="[
                            'px-6 py-2.5 rounded-xl font-semibold text-sm transition-all',
                            action.secondary
                                ? 'border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm hover:shadow-indigo-200 dark:hover:shadow-indigo-900'
                        ]"
                    >{{ action.label }}</a>
                    <Link v-else :href="action.href"
                        :class="[
                            'px-6 py-2.5 rounded-xl font-semibold text-sm transition-all',
                            action.secondary
                                ? 'border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm hover:shadow-indigo-200 dark:hover:shadow-indigo-900'
                        ]"
                    >{{ action.label }}</Link>
                </template>
            </div>

            <!-- Back link -->
            <a @click.prevent="history.back()" href="#"
                class="mt-5 inline-block text-sm text-gray-400 hover:text-indigo-500 dark:hover:text-indigo-400 transition-colors cursor-pointer">
                ← Go back
            </a>
        </div>
    </div>
</template>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-12px); }
}
.animate-float {
    animation: float 3.5s ease-in-out infinite;
}
</style>
