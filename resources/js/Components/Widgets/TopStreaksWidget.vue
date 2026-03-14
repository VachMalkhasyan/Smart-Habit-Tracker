<template>
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5 h-full flex flex-col">
        <h2 class="font-semibold text-gray-800 dark:text-gray-200 mb-4 shrink-0">🔥 Top Streaks</h2>
        <div class="space-y-3 flex-1 overflow-y-auto">
            <div v-if="topStreaks.length === 0" class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">
                No streaks yet
            </div>
            <div v-for="habit in topStreaks" :key="habit.id"
                 class="flex items-center justify-between">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ habit.name }}</span>
                </div>
                <div class="flex items-center gap-1 text-orange-500 shrink-0">
                    <Flame class="w-3.5 h-3.5" />
                    <span class="text-sm font-bold">{{ habit.current_streak }} days</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Flame } from 'lucide-vue-next'

const props = defineProps({
    habits: { type: Array, default: () => [] }
})

const topStreaks = computed(() =>
    [...(props.habits ?? [])]
        .filter(h => h.current_streak > 0)
        .sort((a, b) => b.current_streak - a.current_streak)
        .slice(0, 4)
)
</script>
