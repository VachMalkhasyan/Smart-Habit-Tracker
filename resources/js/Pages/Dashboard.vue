<template>
    <AppLayout title="Dashboard" subtitle="Here's how your habits are going today">
        <template #header-actions>
            <div class="flex items-center gap-3">
                <Button @click="toggleEditMode" :class="isEditMode ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300'">
                    <Settings class="w-4 h-4 mr-2" />
                    {{ isEditMode ? 'Done Editing' : 'Customize' }}
                </Button>
                <Link :href="route('habits.create')">
                    <Button class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2">
                        <Plus class="w-4 h-4" />
                        New Habit
                    </Button>
                </Link>
            </div>
        </template>

        <div v-if="isEditMode" class="bg-indigo-50 dark:bg-indigo-900/30 rounded-xl p-4 mb-6 border border-indigo-100 dark:border-indigo-800 flex items-center justify-between">
            <span class="text-sm text-indigo-800 dark:text-indigo-200 font-medium">✨ Dashboard Edit Mode Active — Drag widgets to rearrange them.</span>

            <Dropdown align="right" width="64" v-if="availableWidgets.length > 0">
                <template #trigger>
                    <Button size="sm" class="bg-indigo-600 hover:bg-indigo-700 text-white gap-1">
                        <Plus class="w-4 h-4" /> Add Widget
                    </Button>
                </template>
                <template #content>
                    <button v-for="widget in availableWidgets" :key="widget.type" @click="addWidget(widget)" class="block w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                        {{ widget.label }}
                    </button>
                </template>
            </Dropdown>
        </div>

        <div v-if="layout.length === 0" class="text-center py-20 text-gray-400">
            <div class="inline-flex w-16 h-16 rounded-full bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800 items-center justify-center mb-4">
                <LayoutDashboard class="w-8 h-8" />
            </div>
            <p>Your dashboard is currently empty.</p>
            <p v-if="!isEditMode" class="mt-2 text-sm text-indigo-500 cursor-pointer" @click="isEditMode = true">Click 'Customize' to start building it.</p>
        </div>

        <draggable
            v-model="layout"
            item-key="id"
            class="grid grid-cols-12 gap-6"
            handle=".drag-handle"
            @end="saveLayoutToServer"
            :animation="200"
        >
            <template #item="{ element }">
                <div :class="['col-span-12 relative group', widthClass(element.w)]" :style="{ minHeight: '120px' }">
                    <div v-if="isEditMode" class="absolute -top-3 -right-3 z-30 flex items-center gap-1">
                        <!-- Width Toggle -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow rounded-lg flex items-center overflow-hidden h-8 divide-x divide-gray-100 dark:divide-gray-700">
                            <button @click="changeWidth(element, 4)" :class="['px-2 text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors', element.w === 4 || element.w === '4' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' : 'text-gray-500']" title="1/3 Width">1/3</button>
                            <button @click="changeWidth(element, 6)" :class="['px-2 text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors', element.w === 6 || element.w === '6' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' : 'text-gray-500']" title="1/2 Width">1/2</button>
                            <button @click="changeWidth(element, 8)" :class="['px-2 text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors', element.w === 8 || element.w === '8' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' : 'text-gray-500']" title="2/3 Width">2/3</button>
                            <button @click="changeWidth(element, 12)" :class="['px-2 text-xs font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors', element.w === 12 || element.w === '12' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' : 'text-gray-500']" title="Full Width">Full</button>
                        </div>

                        <!-- Drag Handle -->
                        <div class="drag-handle w-8 h-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow flex items-center justify-center rounded-full cursor-move text-gray-400 hover:text-indigo-600 transition-colors">
                            <GripHorizontal class="w-4 h-4" />
                        </div>
                    </div>

                    <button v-if="isEditMode && !element.is_core" @click="removeWidget(element.id)" class="absolute -top-3 -left-3 z-30 w-8 h-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow flex items-center justify-center rounded-full text-red-400 hover:text-red-600 transition-colors">
                        <X class="w-4 h-4" />
                    </button>
                    <div v-if="isEditMode && element.is_core" class="absolute -top-3 -left-3 z-30 w-8 h-8 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow flex items-center justify-center rounded-full text-gray-400" title="Core widget cannot be removed">
                        <Lock class="w-3.5 h-3.5" />
                    </div>

                    <!-- Focus overlay during edit mode to highlight dragging -->
                    <div v-if="isEditMode" class="absolute inset-0 border-2 border-dashed border-indigo-300 dark:border-indigo-700 rounded-2xl pointer-events-none z-20 transition-all opacity-0 group-hover:opacity-100 group-active:opacity-100 bg-indigo-50/10 hidden md:block"></div>

                    <component
                        :is="widgetMap[element.type]"
                        v-bind="getPropsForWidget(element)"
                        @update-config="(config) => updateWidgetConfig(element.id, config)"
                    />
                </div>
            </template>
        </draggable>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {useRealtime} from "@/composables/useRealtime.js";
import draggable from 'vuedraggable'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import Dropdown from '@/Components/Dropdown.vue'
import { Plus, Settings, GripHorizontal, X, LayoutDashboard, Lock } from 'lucide-vue-next'

// Import all widgets
import StatCardWidget from '@/Components/Widgets/StatCardWidget.vue'
import TodayHabitsWidget from '@/Components/Widgets/TodayHabitsWidget.vue'
import WeeklyProgressWidget from '@/Components/Widgets/WeeklyProgressWidget.vue'
import TopStreaksWidget from '@/Components/Widgets/TopStreaksWidget.vue'
import MonthlyTrendWidget from '@/Components/Widgets/MonthlyTrendWidget.vue'
import PinnedFriendWidget from '@/Components/Widgets/PinnedFriendWidget.vue'
import QuickPomodoroWidget from '@/Components/Widgets/QuickPomodoroWidget.vue'
import DashboardNoteWidget from '@/Components/Widgets/DashboardNoteWidget.vue'


const props = defineProps({
    habits: Array,
    weeklyData: Array,
    monthlyTrend: Array,
    friends: Array,
    dashboardLayout: Array,
    totalActive: Number,
    totalCompleted: Number,
    longestStreak: Number,
    todayCompleted: Number,
    dashboard_note: String,
})

const isEditMode = ref(false)
const layout = ref(props.dashboardLayout || [])

const widgetMap = {
    StatCardWidget,
    TodayHabitsWidget,
    WeeklyProgressWidget,
    TopStreaksWidget,
    MonthlyTrendWidget,
    PinnedFriendWidget,
    QuickPomodoroWidget,
    DashboardNoteWidget,
}

// Registry of all allowed widgets that users can add
const WIDGET_REGISTRY = [
    { type: 'StatCardWidget', label: 'Stat: Active Habits', w: 3, config: { statType: 'active_habits' } },
    { type: 'StatCardWidget', label: 'Stat: Done Today', w: 3, config: { statType: 'done_today' } },
    { type: 'StatCardWidget', label: 'Stat: Completed All-time', w: 3, config: { statType: 'completed_all' } },
    { type: 'StatCardWidget', label: 'Stat: Longest Streak', w: 3, config: { statType: 'longest_streak' } },
    { type: 'TodayHabitsWidget', label: 'Today\'s Habits List', w: 8 },
    { type: 'DashboardNoteWidget', label: 'Pinned Note', w: 4 },
    { type: 'WeeklyProgressWidget', label: 'Weekly Chart', w: 4 },
    { type: 'TopStreaksWidget', label: 'Top Streaks List', w: 4 },
    { type: 'MonthlyTrendWidget', label: 'Monthly Trend Chart', w: 4 },
    { type: 'PinnedFriendWidget', label: 'Pinned Friend Activity', w: 4 },
    { type: 'QuickPomodoroWidget', label: 'Quick Pomodoro', w: 4 },
]

// Allow multiple instances except for singletons like TodayHabits
const availableWidgets = computed(() => {
    return WIDGET_REGISTRY.filter(w => {
        if (w.type === 'TodayHabitsWidget') {
            return !layout.value.some(l => l.type === w.type)
        }
        if (w.type === 'StatCardWidget') {
            return !layout.value.some(l => l.type === 'StatCardWidget' && l.config?.statType === w.config?.statType)
        }
        return true
    })
})

const { on } = useRealtime()

const habits = ref(props.habits)
const xpProgress = ref(props.xpProgress)

on('onHabitCompleted', (e) => {
    const habit = habits.value.find(h => h.id === e.habit_id)
    if (habit) {
        habit.completed_today  = e.is_done
        habit.current_streak   = e.current_streak
    }
})

on('onXpAwarded', (e) => {
    xpProgress.value = e.xp_progress
})

const toggleEditMode = () => {
    isEditMode.value = !isEditMode.value
}

const changeWidth = (element, newWidth) => {
    element.w = newWidth
    saveLayoutToServer()
}

const addWidget = (widgetInfo) => {
    layout.value.unshift({
        id: 'widget_' + Date.now() + Math.floor(Math.random() * 1000),
        type: widgetInfo.type,
        w: widgetInfo.w,
        config: widgetInfo.config || {}
    })
    saveLayoutToServer()
}

const removeWidget = (id) => {
    layout.value = layout.value.filter(l => l.id !== id)
    saveLayoutToServer()
}

const updateWidgetConfig = (id, config) => {
    const item = layout.value.find(l => l.id === id)
    if (item) {
        item.config = config
        saveLayoutToServer()
    }
}

const saveLayoutToServer = () => {
    router.post(route('dashboard.layout.update'), {
        dashboard_layout: layout.value
    }, { preserveScroll: true })
}

const widthClass = (w) => {
    return {
        4: 'md:col-span-4',
        6: 'md:col-span-6',
        8: 'md:col-span-8',
        12: 'md:col-span-12'
    }[w] || 'md:col-span-12'
}
const getPropsForWidget = (element) => {
    const base = { widgetConfig: element.config || {} }

    switch (element.type) {
        case 'StatCardWidget':
            return { ...base, totalActive: props.totalActive, todayCompleted: props.todayCompleted, totalCompleted: props.totalCompleted, longestStreak: props.longestStreak, habits: props.habits }
        case 'TodayHabitsWidget':
            return { ...base, habits: props.habits, todayCompleted: props.todayCompleted }
        case 'WeeklyProgressWidget':
            return { ...base, weeklyData: props.weeklyData }
        case 'TopStreaksWidget':
            return { ...base, habits: props.habits }
        case 'MonthlyTrendWidget':
            return { ...base, monthlyTrend: props.monthlyTrend }
        case 'PinnedFriendWidget':
            return { ...base, friends: props.friends }
        case 'QuickPomodoroWidget':
            return base
        case 'DashboardNoteWidget':
            return { ...base, initialNote: props.dashboard_note }
    }
    return base
}

</script>
