<template>
    <div class="bg-white dark:bg-gray-900 rounded-2xl p-5 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow h-full flex flex-col justify-center">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ statConfig.label }}</span>
            <div :class="`p-2 rounded-xl ${statConfig.bg}`">
                <component :is="statConfig.icon" :class="`w-4 h-4 ${statConfig.color}`" />
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ statConfig.value }}</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ statConfig.hint }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { TrendingUp, CalendarCheck, Target, Award } from 'lucide-vue-next'

const props = defineProps({
    widgetConfig: { type: Object, default: () => ({}) },
    totalActive: Number,
    todayCompleted: Number,
    totalCompleted: Number,
    longestStreak: Number,
    habits: { type: Array, default: () => [] }
})

const statConfig = computed(() => {
    const type = props.widgetConfig.statType || 'active_habits'
    
    switch (type) {
        case 'active_habits':
            return {
                label: 'Active Habits',
                value: props.totalActive ?? 0,
                hint: 'Currently tracking',
                icon: Target,
                bg: 'bg-indigo-50 dark:bg-indigo-900/30',
                color: 'text-indigo-600 dark:text-indigo-400'
            }
        case 'done_today':
            return {
                label: 'Done Today',
                value: props.todayCompleted ?? 0,
                hint: `Out of ${props.habits?.length ?? 0} habits`,
                icon: CalendarCheck,
                bg: 'bg-green-50 dark:bg-green-900/30',
                color: 'text-green-600 dark:text-green-400'
            }
        case 'completed_all':
            return {
                label: 'Completed',
                value: props.totalCompleted ?? 0,
                hint: 'All time',
                icon: Award,
                bg: 'bg-yellow-50 dark:bg-yellow-900/30',
                color: 'text-yellow-600 dark:text-yellow-400'
            }
        case 'longest_streak':
            return {
                label: 'Longest Streak',
                value: `${props.longestStreak ?? 0}d`,
                hint: 'Personal best',
                icon: TrendingUp,
                bg: 'bg-orange-50 dark:bg-orange-900/30',
                color: 'text-orange-500 dark:text-orange-400'
            }
        default:
            return { label: 'Unknown', value: 0, hint: '', icon: Target, bg: '', color: '' }
    }
})
</script>
