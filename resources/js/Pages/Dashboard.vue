<template>
    <AppLayout title="Dashboard" subtitle="Here's how your habits are going today">

        <template #header-actions>
            <Link :href="route('habits.create')">
                <Button class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2">
                    <Plus class="w-4 h-4" />
                    New Habit
                </Button>
            </Link>
        </template>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
            <div v-for="stat in stats" :key="stat.label"
                 class="bg-white dark:bg-gray-900 rounded-2xl p-5 border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ stat.label }}</span>
                    <div :class="`p-2 rounded-xl ${stat.bg}`">
                        <component :is="stat.icon" :class="`w-4 h-4 ${stat.color}`" />
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stat.value }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ stat.hint }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- Today's Habits -->
            <div class="xl:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-200">Today's Habits</h2>
                    <span class="text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">
                        {{ todayCompleted }}/{{ habits.length }} done
                    </span>
                </div>

                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    <div v-if="habits.length === 0" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                        <ListChecks class="w-10 h-10 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">No habits yet. Create your first one!</p>
                    </div>

                    <div v-for="habit in habits" :key="habit.id"
                         class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:bg-gray-800 transition-colors group">

                        <!-- Check button -->
                        <button @click="toggleHabit(habit)"
                                :class="[
                                'w-8 h-8 rounded-full border-2 flex items-center justify-center shrink-0 transition-all',
                                habit.is_done_today
                                    ? 'bg-indigo-600 border-indigo-600 text-white'
                                    : 'border-gray-300 hover:border-indigo-400'
                            ]">
                            <Check v-if="habit.is_done_today" class="w-4 h-4" />
                        </button>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p :class="['text-sm font-medium truncate', habit.is_done_today ? 'line-through text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-gray-200']">
                                {{ habit.name }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                {{ habit.category?.name ?? 'No category' }} •
                                {{ habit.today_count }}/{{ habit.repeat_count }}x today
                            </p>
                        </div>

                        <!-- Streak -->
                        <div class="flex items-center gap-1 text-orange-500">
                            <Flame class="w-4 h-4" />
                            <span class="text-sm font-semibold">{{ habit.current_streak }}</span>
                        </div>

                        <!-- Priority badge -->
                        <span :class="priorityClass(habit.priority)"
                              class="text-xs px-2 py-0.5 rounded-full font-medium hidden group-hover:inline-block">
                            {{ priorityLabel(habit.priority) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="flex flex-col gap-6">

                <!-- Weekly Progress Chart -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Weekly Progress</h2>
                    <apexchart
                        type="bar"
                        height="180"
                        :options="chartOptions"
                        :series="chartSeries"
                    />
                </div>

                <!-- Top Streaks -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">🔥 Top Streaks</h2>
                    <div class="space-y-3">
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

            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
    Plus, Check, Flame, ListChecks,
    TrendingUp, CalendarCheck, Target, Award
} from 'lucide-vue-next'

const props = defineProps({
    habits: Array,
    weeklyData: Array,   // [{ day: 'Mon', completed: 3 }]
    totalActive: Number,
    totalCompleted: Number,
    longestStreak: Number,
    todayCompleted: Number,
})

const isDark = computed(() =>
    document.documentElement.classList.contains('dark')
)

// Stats cards
const stats = computed(() => [
    {
        label: 'Active Habits',
        value: props.totalActive ?? 0,
        hint: 'Currently tracking',
        icon: Target,
        bg: 'bg-indigo-50',
        color: 'text-indigo-600'
    },
    {
        label: 'Done Today',
        value: props.todayCompleted ?? 0,
        hint: `Out of ${props.habits?.length ?? 0} habits`,
        icon: CalendarCheck,
        bg: 'bg-green-50',
        color: 'text-green-600'
    },
    {
        label: 'Completed',
        value: props.totalCompleted ?? 0,
        hint: 'All time',
        icon: Award,
        bg: 'bg-yellow-50',
        color: 'text-yellow-600'
    },
    {
        label: 'Longest Streak',
        value: `${props.longestStreak ?? 0}d`,
        hint: 'Personal best',
        icon: TrendingUp,
        bg: 'bg-orange-50',
        color: 'text-orange-500'
    },
])

// Top 4 streaks
const topStreaks = computed(() =>
    [...(props.habits ?? [])]
        .filter(h => h.current_streak > 0)
        .sort((a, b) => b.current_streak - a.current_streak)
        .slice(0, 4)
)

// Priority helpers
const priorityClass = (p) => ({
    1: 'bg-red-100 text-red-600',
    2: 'bg-yellow-100 text-yellow-600',
    3: 'bg-green-100 text-green-600',
}[p] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 dark:text-gray-500')

const priorityLabel = (p) => ({ 1: 'High', 2: 'Medium', 3: 'Low' }[p] ?? '')

// Chart
const chartOptions = computed(() => ({
    chart: { toolbar: { show: false }, sparkline: { enabled: false } },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
    dataLabels: { enabled: false },
    theme: {
        mode: isDark.value ? 'dark' : 'light'
    },
    xaxis: {
        categories: props.weeklyData?.map(d => d.day) ?? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        labels: { style: { fontSize: '11px', colors: '#9ca3af' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { style: { colors: '#9ca3af' } } },
    grid: { borderColor: isDark.value ? '#374151' : '#f3f4f6', strokeDashArray: 4 },
    colors: ['#6366f1'],
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

const chartSeries = [{
    name: 'Completed',
    data: props.weeklyData?.map(d => d.completed) ?? [0,0,0,0,0,0,0],
}]

// Toggle habit completion
const toggleHabit = (habit) => {
    router.post(route('completions.toggle', habit.id), {}, {
        preserveScroll: true,
    })
}
</script>
