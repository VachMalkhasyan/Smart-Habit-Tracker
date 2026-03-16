<template>
    <AppLayout :title="habit.name" subtitle="Habit details & progress">

        <template #header-actions>
            <div class="flex items-center gap-2">
                <Link :href="route('habits.edit', habit.id)">
                    <Button variant="outline" class="gap-2">
                        <Pencil class="w-4 h-4" /> Edit
                    </Button>
                </Link>
                <Button @click="confirmDelete = true"
                        variant="outline"
                        class="gap-2 text-red-500 hover:text-red-600 hover:border-red-300">
                    <Trash2 class="w-4 h-4" /> Delete
                </Button>
            </div>
        </template>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- Left Column -->
            <div class="xl:col-span-2 space-y-6">

                <!-- Overview Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span :class="priorityDot(habit.priority)"></span>
                                <span class="text-xs text-gray-400">{{ habit.category?.name ?? 'No category' }}</span>
                                <span :class="statusClass(habit.status)"
                                      class="text-xs px-2 py-0.5 rounded-full font-medium capitalize ml-1">
                                    {{ habit.status }}
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900">{{ habit.name }}</h2>
                            <p v-if="habit.description" class="text-sm text-gray-500 mt-1">
                                {{ habit.description }}
                            </p>
                        </div>

                        <!-- Today check-in -->
                        <button @click="toggleToday"
                                :class="[
                                'flex flex-col items-center gap-1 px-4 py-3 rounded-2xl border-2 transition-all',
                                todayCompletion?.is_done
                                    ? 'bg-indigo-600 border-indigo-600 text-white'
                                    : 'border-gray-200 hover:border-indigo-400 text-gray-500'
                            ]">
                            <Check class="w-6 h-6" />
                            <span class="text-xs font-medium">
                                {{ todayCompletion?.is_done ? 'Done!' : 'Check in' }}
                            </span>
                        </button>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm text-gray-500">Overall Progress</span>
                        <span class="text-sm font-semibold text-indigo-600">{{ progressPercent }}%</span>
                    </div>
                    <div class="w-full h-2.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 rounded-full transition-all duration-500"
                             :style="{ width: progressPercent + '%' }">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">
                        {{ habit.current_streak }} / {{ habit.goal }} {{ habit.goal_unit }}
                    </p>
                </div>

                <!-- Completion Chart -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">Last 30 Days</h3>
                        <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full">
                            {{ completionRate }}% completion rate
                        </span>
                    </div>
                    <apexchart
                        type="area"
                        height="200"
                        :options="areaChartOptions"
                        :series="areaChartSeries"
                    />
                </div>

                <!-- Completion History -->
                <div v-for="completion in completions" :key="completion.id"
                     class="flex items-center justify-between px-6 py-3">
                    <div class="flex items-center gap-3">
                        <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center',
            completion.is_done ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-100 dark:bg-gray-800'
        ]">
                            <Check v-if="completion.is_done" class="w-4 h-4 text-green-600" />
                            <X v-else class="w-4 h-4 text-gray-400" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ formatDate(completion.completed_at) }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                {{ completion.count }} / {{ habit.repeat_count }} times
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Cheers from friends -->
                        <div v-if="completion.cheers?.length > 0"
                             class="flex items-center gap-1">
            <span v-for="cheer in completion.cheers" :key="cheer.id"
                  :title="cheer.user?.name"
                  class="text-sm cursor-default">
                {{ cheer.emoji }}
            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">
                {{ completion.cheers.length }} cheer{{ completion.cheers.length !== 1 ? 's' : '' }}
            </span>
                        </div>

                        <span :class="completion.is_done ? 'text-green-600 bg-green-50 dark:bg-green-900 dark:text-green-400' : 'text-gray-400 bg-gray-50 dark:bg-gray-800'"
                              class="text-xs px-2.5 py-1 rounded-full font-medium">
            {{ completion.is_done ? 'Completed' : 'Partial' }}
        </span>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-5">

                <!-- Streak Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Streaks</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-9 h-9 bg-orange-50 rounded-xl flex items-center justify-center">
                                    <Flame class="w-5 h-5 text-orange-500" />
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Current Streak</p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ habit.current_streak }} days
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-9 h-9 bg-yellow-50 rounded-xl flex items-center justify-center">
                                    <Award class="w-5 h-5 text-yellow-500" />
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Longest Streak</p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ habit.longest_streak }} days
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Details</h3>
                    <div class="space-y-3">
                        <div v-for="detail in details" :key="detail.label"
                             class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <component :is="detail.icon" class="w-4 h-4 text-gray-400" />
                                <span class="text-sm text-gray-500">{{ detail.label }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-800">{{ detail.value }}</span>
                        </div>
                    </div>
                </div>

                <!-- Today's Progress Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Today</h3>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-gray-500">Check-ins</span>
                        <span class="text-sm font-semibold text-indigo-600">
                            {{ todayCompletion?.count ?? 0 }} / {{ habit.repeat_count }}
                        </span>
                    </div>
                    <div class="w-full h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 rounded-full transition-all"
                             :style="{ width: todayPercent + '%' }">
                        </div>
                    </div>

                    <!-- Manual count buttons -->
                    <div class="flex items-center justify-between mt-4">
                        <button @click="decrementToday"
                                :disabled="!todayCompletion || todayCompletion.count <= 0"
                                class="w-9 h-9 rounded-xl border border-gray-200 flex items-center justify-center hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">
                            <Minus class="w-4 h-4 text-gray-600" />
                        </button>
                        <span class="text-2xl font-bold text-gray-900">
                            {{ todayCompletion?.count ?? 0 }}
                        </span>
                        <button @click="incrementToday"
                                class="w-9 h-9 rounded-xl border border-indigo-200 bg-indigo-50 flex items-center justify-center hover:bg-indigo-100">
                            <Plus class="w-4 h-4 text-indigo-600" />
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Delete Dialog -->
        <Dialog v-model:open="confirmDelete">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Habit</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete <strong>{{ habit.name }}</strong>?
                        All completion history will be lost.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="confirmDelete = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteHabit">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription, DialogFooter
} from '@/components/ui/dialog'
import {
    Check, X, Pencil, Trash2, Flame, Award,
    Plus, Minus, Calendar, Target, RefreshCw, Flag
} from 'lucide-vue-next'
import dayjs from 'dayjs'

const props = defineProps({
    habit: Object,
    completions: Array,   // last 30 days
    todayCompletion: Object,
})

const habit = ref(props.habit)

const isDark = computed(() =>
    document.documentElement.classList.contains('dark')
)

const confirmDelete = ref(false)

// Progress
const progressPercent = computed(() =>
    Math.min(Math.round((habit.value.current_streak / habit.value.goal) * 100), 100)
)

// Today percent
const todayPercent = computed(() => {
    const count = props.todayCompletion?.count ?? 0
    return Math.min(Math.round((count / habit.value.repeat_count) * 100), 100)
})

// Completion rate (last 30 days)
const completionRate = computed(() => {
    if (!props.completions?.length) return 0
    const done = props.completions.filter(c => c.is_done).length
    return Math.round((done / props.completions.length) * 100)
})

// Details list
const details = computed(() => {
    const list = [
        { label: 'Start Date',  value: dayjs(habit.value.start_date).format('MMM D, YYYY'), icon: Calendar },
        { label: 'Goal',        value: `${habit.value.goal} ${habit.value.goal_unit}`, icon: Target },
        { label: 'Priority',    value: { 1: 'High', 2: 'Medium', 3: 'Low' }[habit.value.priority], icon: Flag },
    ]

    if (habit.value.deadline_value && habit.value.deadline_unit) {
        list.push({
            label: 'Duration',
            value: `${habit.value.deadline_value} ${habit.value.deadline_unit}`,
            icon: RefreshCw
        })
        list.push({
            label: 'End Date',
            value: dayjs(habit.value.start_date).add(habit.value.deadline_value, habit.value.deadline_unit).format('MMM D, YYYY'),
            icon: Calendar
        })
    }

    return list
})

// Helpers
const formatDate = (date) => dayjs(date).format('MMM D, YYYY')

const priorityDot = (p) => ({
    1: 'w-2 h-2 rounded-full bg-red-400 shrink-0',
    2: 'w-2 h-2 rounded-full bg-yellow-400 shrink-0',
    3: 'w-2 h-2 rounded-full bg-green-400 shrink-0',
}[p] ?? 'w-2 h-2 rounded-full bg-gray-300')

const statusClass = (s) => ({
    active:    'bg-green-100 text-green-700',
    inactive:  'bg-gray-100 text-gray-500',
    completed: 'bg-blue-100 text-blue-700',
    paused:    'bg-yellow-100 text-yellow-700',
}[s] ?? '')

// Chart
const areaChartOptions = computed(() => ({
    chart: { toolbar: { show: false }, sparkline: { enabled: false } },
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    dataLabels: { enabled: false },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    xaxis: {
        categories: props.completions?.map(c => dayjs(c.completed_at).format('MMM D')) ?? [],
        labels: { show: false },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { style: { colors: '#9ca3af' } } },
    grid: { borderColor: isDark.value ? '#374151' : '#f3f4f6', strokeDashArray: 4 },
    colors: ['#6366f1'],
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

const areaChartSeries = [{
    name: 'Completions',
    data: props.completions?.map(c => c.count) ?? [],
}]

// Actions
const toggleToday = () => {
    router.post(route('completions.toggle', habit.value.id), {}, { preserveScroll: true })
}

const incrementToday = () => {
    router.post(route('completions.increment', habit.value.id), {}, { preserveScroll: true })
}

const decrementToday = () => {
    router.post(route('completions.decrement', habit.value.id), {}, { preserveScroll: true })
}

const deleteHabit = () => {
    router.delete(route('habits.destroy', habit.value.id))
}

import { useRealtime } from '@/composables/useRealtime'
const { on } = useRealtime()

on('onHabitCompleted', (e) => {
    if (e.habit_id === habit.value.id) {
        habit.value.completed_today = e.is_done
        habit.value.current_streak  = e.current_streak
    }
})
</script>
