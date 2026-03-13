<template>
    <AppLayout :title="profileUser.name" subtitle="Public profile">

        <div class="max-w-2xl mx-auto space-y-6">

            <!-- Profile Card -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                <div class="flex items-start gap-4">
                    <img :src="profileUser.profile_photo_url" :alt="profileUser.name"
                         class="w-16 h-16 rounded-2xl object-cover" />
                    <div class="flex-1 min-w-0">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ profileUser.name }}
                        </h2>
                        <p v-if="profileUser.username"
                           class="text-sm text-indigo-500">@{{ profileUser.username }}</p>
                        <p v-if="profileUser.bio"
                           class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ profileUser.bio }}</p>
                    </div>

                    <!-- Friend action -->
                    <div class="shrink-0">
                        <Button v-if="!isFriend && !hasPending"
                                @click="sendRequest"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2">
                            <UserPlus class="w-4 h-4" /> Add Friend
                        </Button>
                        <span v-else-if="hasPending"
                              class="text-sm text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-gray-700 px-3 py-2 rounded-xl">
                            Request Sent
                        </span>
                        <span v-else
                              class="text-sm text-green-500 flex items-center gap-1.5 border border-green-200 dark:border-green-800 px-3 py-2 rounded-xl">
                            <Check class="w-4 h-4" /> Friends
                        </span>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-4 gap-3 mt-5">
                    <div v-for="stat in profileStats" :key="stat.label"
                         class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ stat.value }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ stat.label }}</p>
                    </div>
                </div>
            </div>

            <!-- Active Habits -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">Active Habits</h3>
                </div>
                <div v-if="habits.length === 0"
                     class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                    No public habits
                </div>
                <div v-else class="divide-y divide-gray-50 dark:divide-gray-800">
                    <div v-for="habit in habits" :key="habit.id"
                         class="flex items-center gap-3 px-6 py-4">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ habit.name }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ habit.category ?? 'No category' }}</p>
                        </div>
                        <div class="flex items-center gap-1 text-orange-500 shrink-0">
                            <Flame class="w-4 h-4" />
                            <span class="text-sm font-bold">{{ habit.current_streak }}d</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { UserPlus, Check, Flame } from 'lucide-vue-next'

const props = defineProps({
    profileUser: Object,
    habits:      Array,
    stats:       Object,
    isFriend:    Boolean,
    hasPending:  Boolean,
})

const profileStats = computed(() => [
    { label: 'Habits',       value: props.stats.total_habits },
    { label: 'Active',       value: props.stats.active_habits },
    { label: 'Best Streak',  value: `${props.stats.longest_streak}d` },
    { label: 'Completions',  value: props.stats.total_completions },
])

const sendRequest = () => {
    router.post(route('friends.request', props.profileUser.id), {}, {
        preserveScroll: true
    })
}
</script>
