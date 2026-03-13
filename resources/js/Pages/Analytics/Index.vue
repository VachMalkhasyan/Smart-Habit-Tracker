<template>
    <AppLayout title="Analytics" subtitle="Deep dive into your habit performance">

        <template #header-actions>
            <ExportButton />
        </template>

        <!-- Overview Stats -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
            <div v-for="stat in overviewStats" :key="stat.label"
                 class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ stat.label }}</span>
                    <div :class="`p-2 rounded-xl ${stat.bg}`">
                        <component :is="stat.icon" :class="`w-4 h-4 ${stat.color}`" />
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stat.value }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ stat.hint }}</p>
            </div>
        </div>

        <!-- Heatmap -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Activity Heatmap</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Last year of completions</p>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                    <span>Less</span>
                    <div class="flex gap-1">
                        <div v-for="level in heatmapLevels" :key="level"
                             :class="heatmapColor(level)"
                             class="w-3 h-3 rounded-sm">
                        </div>
                    </div>
                    <span>More</span>
                </div>
            </div>

            <!-- Month labels -->
            <div class="overflow-x-auto">
                <div class="min-w-max">
                    <!-- Month labels -->
                    <div class="flex gap-1 mb-1 ml-8">
                        <div v-for="month in monthLabels" :key="month.label"
                             :style="{ width: (month.weeks * 14) + 'px' }"
                             class="text-xs text-gray-400 dark:text-gray-500 shrink-0">
                            {{ month.label }}
                        </div>
                    </div>

                    <div class="flex gap-1">
                        <!-- Day labels -->
                        <div class="flex flex-col gap-1 mr-1">
                            <div v-for="day in ['', 'Mon', '', 'Wed', '', 'Fri', '']" :key="day"
                                 class="h-3 text-xs text-gray-400 dark:text-gray-500 leading-3 w-6 text-right">
                                {{ day }}
                            </div>
                        </div>

                        <!-- Weeks -->
                        <div v-for="(week, wi) in heatmapWeeks" :key="wi" class="flex flex-col gap-1">
                            <div v-for="(day, di) in week" :key="di"
                                 :class="[
                                    'w-3 h-3 rounded-sm transition-all cursor-pointer',
                                    day ? heatmapColor(day.count) : 'bg-gray-100 dark:bg-gray-800'
                                ]"
                                 :title="day ? `${day.date}: ${day.count} completions` : ''"
                                 @mouseenter="hoveredDay = day"
                                 @mouseleave="hoveredDay = null">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tooltip -->
            <div v-if="hoveredDay"
                 class="mt-3 text-xs text-gray-500 dark:text-gray-400 text-center">
                <strong class="text-gray-800 dark:text-gray-200">{{ hoveredDay.date }}</strong>
                — {{ hoveredDay.count }} habit{{ hoveredDay.count !== 1 ? 's' : '' }} completed
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">

            <!-- Best Day of Week -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-1">Best Day of Week</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-5">Total completions per weekday</p>

                <div class="space-y-3">
                    <div v-for="(count, day) in dayStats" :key="day"
                         class="flex items-center gap-3">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-8 shrink-0">
                            {{ day }}
                        </span>
                        <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                            <div class="h-full rounded-lg transition-all duration-500"
                                 :class="count === maxDayCount ? 'bg-indigo-500' : 'bg-indigo-200 dark:bg-indigo-900'"
                                 :style="{ width: maxDayCount > 0 ? (count / maxDayCount * 100) + '%' : '0%' }">
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 w-6 text-right shrink-0">
                            {{ count }}
                        </span>
                        <span v-if="count === maxDayCount"
                              class="text-xs text-indigo-500 font-medium shrink-0">
                            🏆 Best
                        </span>
                    </div>
                </div>
            </div>

            <!-- Monthly Trend -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-1">Monthly Trend</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Completions over last 6 months</p>
                <apexchart
                    type="area"
                    height="200"
                    :options="monthlyChartOptions"
                    :series="monthlyChartSeries"
                />
            </div>
        </div>

        <!-- Per Habit Stats -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-1">Habit Performance</h2>
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-5">Completion rate per habit</p>

            <div v-if="habitStats.length === 0" class="text-center py-8 text-gray-400 text-sm">
                No habit data yet
            </div>

            <div v-else class="space-y-4">
                <div v-for="habit in habitStats" :key="habit.name"
                     class="flex items-center gap-4">
                    <div class="w-40 shrink-0">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                            {{ habit.name }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            {{ habit.total_completions }} completions
                        </p>
                    </div>
                    <div class="flex-1 h-4 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                             :class="rateColor(habit.completion_rate)"
                             :style="{ width: habit.completion_rate + '%' }">
                        </div>
                    </div>
                    <span class="text-sm font-bold w-12 text-right shrink-0"
                          :class="rateTextColor(habit.completion_rate)">
                        {{ habit.completion_rate }}%
                    </span>
                    <div class="flex items-center gap-1 text-orange-500 shrink-0 w-16">
                        <Flame class="w-3.5 h-3.5" />
                        <span class="text-xs font-semibold">{{ habit.current_streak }}d</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Streak Comparison -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-1">Streak Comparison</h2>
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Current vs longest streaks</p>
            <apexchart
                type="bar"
                height="250"
                :options="streakChartOptions"
                :series="streakChartSeries"
            />
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ExportButton from '@/Components/ExportButton.vue'
import { Flame, CalendarCheck, Award, TrendingUp, Activity } from 'lucide-vue-next'
import dayjs from 'dayjs'

const props = defineProps({
    heatmap:           Object,
    dayStats:          Object,
    monthlyTrend:      Array,
    habitStats:        Array,
    totalCompletions:  Number,
    activeDays:        Number,
    bestStreak:        Number,
    avgCompletionRate: Number,
})

// Overview stats
const overviewStats = computed(() => [
    {
        label: 'Total Completions',
        value: props.totalCompletions ?? 0,
        hint:  'All time',
        icon:  CalendarCheck,
        bg:    'bg-indigo-50 dark:bg-indigo-950',
        color: 'text-indigo-600',
    },
    {
        label: 'Active Days',
        value: props.activeDays ?? 0,
        hint:  'Days with at least 1 completion',
        icon:  Activity,
        bg:    'bg-green-50 dark:bg-green-950',
        color: 'text-green-600',
    },
    {
        label: 'Best Streak',
        value: `${props.bestStreak ?? 0}d`,
        hint:  'Longest streak ever',
        icon:  Flame,
        bg:    'bg-orange-50 dark:bg-orange-950',
        color: 'text-orange-500',
    },
    {
        label: 'Avg Completion',
        value: `${props.avgCompletionRate ?? 0}%`,
        hint:  'Across all habits',
        icon:  TrendingUp,
        bg:    'bg-yellow-50 dark:bg-yellow-950',
        color: 'text-yellow-600',
    },
])

// ── Heatmap ──────────────────────────────────────────────

const hoveredDay   = ref(null)
const heatmapLevels = [0, 1, 2, 3, 4]

const heatmapColor = (count) => {
    if (!count || count === 0) return 'bg-gray-100 dark:bg-gray-800'
    if (count === 1)           return 'bg-indigo-200 dark:bg-indigo-900'
    if (count === 2)           return 'bg-indigo-300 dark:bg-indigo-700'
    if (count === 3)           return 'bg-indigo-400 dark:bg-indigo-600'
    return 'bg-indigo-600 dark:bg-indigo-400'
}

// Build weeks array for heatmap
const heatmapWeeks = computed(() => {
    const weeks = []
    const end   = dayjs()
    const start = end.subtract(1, 'year').startOf('week')
    let current = start

    while (current.isBefore(end) || current.isSame(end, 'day')) {
        const week = []
        for (let d = 0; d < 7; d++) {
            const date     = current.add(d, 'day')
            const dateStr  = date.format('YYYY-MM-DD')
            const count    = props.heatmap?.[dateStr] ?? 0
            if (date.isAfter(end)) {
                week.push(null)
            } else {
                week.push({ date: dateStr, count })
            }
        }
        weeks.push(week)
        current = current.add(1, 'week')
    }
    return weeks
})

// Month labels for heatmap
const monthLabels = computed(() => {
    const labels  = []
    const end     = dayjs()
    const start   = end.subtract(1, 'year').startOf('week')
    let current   = start
    let weekIndex = 0
    let lastMonth = null

    while (current.isBefore(end)) {
        const month = current.format('MMM')
        if (month !== lastMonth) {
            if (lastMonth !== null) {
                labels[labels.length - 1].weeks = weekIndex - labels[labels.length - 1].startWeek
            }
            labels.push({ label: month, startWeek: weekIndex, weeks: 4 })
            lastMonth = month
        }
        current = current.add(1, 'week')
        weekIndex++
    }
    if (labels.length > 0) {
        labels[labels.length - 1].weeks = weekIndex - labels[labels.length - 1].startWeek
    }
    return labels
})

// ── Best day ──────────────────────────────────────────────

const maxDayCount = computed(() =>
    Math.max(...Object.values(props.dayStats ?? {}), 1)
)

// ── Charts ──────────────────────────────────────────────

const isDark = computed(() =>
    document.documentElement.classList.contains('dark')
)

const monthlyChartOptions = computed(() => ({
    chart:       { toolbar: { show: false } },
    stroke:      { curve: 'smooth', width: 2 },
    fill:        { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    dataLabels:  { enabled: false },
    theme:       { mode: isDark.value ? 'dark' : 'light' },
    xaxis: {
        categories: props.monthlyTrend?.map(d => d.month) ?? [],
        labels:     { style: { colors: '#9ca3af' } },
        axisBorder: { show: false },
        axisTicks:  { show: false },
    },
    yaxis:  { labels: { style: { colors: '#9ca3af' } } },
    grid:   { borderColor: isDark.value ? '#374151' : '#f3f4f6', strokeDashArray: 4 },
    colors: ['#6366f1'],
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

const monthlyChartSeries = [{
    name: 'Completions',
    data: props.monthlyTrend?.map(d => d.total) ?? [],
}]

const streakChartOptions = computed(() => ({
    chart:      { toolbar: { show: false } },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
    dataLabels: { enabled: false },
    theme:      { mode: isDark.value ? 'dark' : 'light' },
    xaxis: {
        categories: props.habitStats?.map(h => h.name.length > 12 ? h.name.substring(0, 12) + '…' : h.name) ?? [],
        labels:     { style: { colors: '#9ca3af', fontSize: '11px' } },
        axisBorder: { show: false },
        axisTicks:  { show: false },
    },
    yaxis:  { labels: { style: { colors: '#9ca3af' } } },
    grid:   { borderColor: isDark.value ? '#374151' : '#f3f4f6', strokeDashArray: 4 },
    colors: ['#6366f1', '#e0e7ff'],
    legend: { labels: { colors: '#9ca3af' } },
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

const streakChartSeries = [
    { name: 'Current Streak',  data: props.habitStats?.map(h => h.current_streak)  ?? [] },
    { name: 'Longest Streak',  data: props.habitStats?.map(h => h.longest_streak) ?? [] },
]

// ── Completion rate colors ──────────────────────────────

const rateColor = (rate) => {
    if (rate >= 80) return 'bg-green-500'
    if (rate >= 60) return 'bg-indigo-500'
    if (rate >= 40) return 'bg-yellow-500'
    return 'bg-red-400'
}

const rateTextColor = (rate) => {
    if (rate >= 80) return 'text-green-600 dark:text-green-400'
    if (rate >= 60) return 'text-indigo-600 dark:text-indigo-400'
    if (rate >= 40) return 'text-yellow-600 dark:text-yellow-400'
    return 'text-red-500'
}
</script>
