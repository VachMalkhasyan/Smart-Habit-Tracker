<template>
    <AppLayout title="Pomodoro" subtitle="Focus timer with habit tracking">

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- Timer -->
            <div class="xl:col-span-2 space-y-5">

                <!-- Timer Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-8 text-center">

                    <!-- Mode tabs -->
                    <div class="flex gap-2 justify-center mb-8">
                        <button v-for="mode in modes" :key="mode.key"
                                @click="switchMode(mode.key)"
                                :class="[
                                'px-5 py-2 rounded-xl text-sm font-medium transition-all border',
                                pomodoro.currentMode === mode.key
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-indigo-300'
                            ]">
                            {{ mode.label }}
                        </button>
                    </div>

                    <!-- Timer display -->
                    <div class="relative w-52 h-52 mx-auto mb-8">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="45" fill="none"
                                    stroke="currentColor" stroke-width="3"
                                    class="text-gray-100 dark:text-gray-800"/>
                            <circle cx="50" cy="50" r="45" fill="none"
                                    stroke="currentColor" stroke-width="3"
                                    stroke-linecap="round"
                                    :stroke-dasharray="circumference"
                                    :stroke-dashoffset="dashOffset"
                                    class="text-indigo-500 transition-all duration-1000"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-5xl font-bold text-gray-900 dark:text-gray-100 tabular-nums">
                                {{ pomodoro.formattedTime }}
                            </span>
                            <span class="text-sm text-gray-400 dark:text-gray-500 mt-1 capitalize">
                                {{ pomodoro.currentMode === 'work' ? 'Focus time' : 'Break time' }}
                            </span>
                        </div>
                    </div>

                    <!-- Session count -->
                    <div class="flex items-center justify-center gap-2 mb-6">
                        <div v-for="i in 4" :key="i"
                             :class="[
                                'w-3 h-3 rounded-full transition-all',
                                i <= totalSessionsCount % 4 || (totalSessionsCount > 0 && totalSessionsCount % 4 === 0 && i === 4)
                                    ? 'bg-indigo-500'
                                    : 'bg-gray-200 dark:bg-gray-700'
                            ]">
                        </div>
                        <span class="text-sm text-gray-400 dark:text-gray-500 ml-2">
                            {{ totalSessionsCount }} sessions today
                        </span>
                    </div>

                    <!-- Controls -->
                    <div class="flex items-center justify-center gap-4">
                        <button @click="pomodoro.reset()"
                                class="w-12 h-12 rounded-full border-2 border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400 hover:border-indigo-300 hover:text-indigo-500 transition-all">
                            <RotateCcw class="w-5 h-5" />
                        </button>

                        <button @click="toggleTimer"
                                :class="[
                                'w-20 h-20 rounded-full flex items-center justify-center text-white transition-all shadow-lg',
                                pomodoro.isRunning
                                    ? 'bg-red-500 hover:bg-red-600'
                                    : 'bg-indigo-600 hover:bg-indigo-700'
                            ]">
                            <Pause v-if="pomodoro.isRunning" class="w-8 h-8" />
                            <Play v-else class="w-8 h-8 ml-1" />
                        </button>

                        <button @click="pomodoro.sessionComplete()"
                                class="w-12 h-12 rounded-full border-2 border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400 hover:border-indigo-300 hover:text-indigo-500 transition-all">
                            <SkipForward class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Linked habit -->
                    <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-800">
                        <label class="text-sm text-gray-500 dark:text-gray-400 mb-2 block">
                            Working on:
                        </label>
                        <select v-model="pomodoro.selectedHabit"
                                class="w-full max-w-xs mx-auto block text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600">
                            <option :value="null">— No habit —</option>
                            <option v-for="h in habits" :key="h.id" :value="h.id">
                                {{ h.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Custom Timer Settings -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <Settings2 class="w-4 h-4 text-indigo-500" />
                        Timer Settings
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-1.5 block">
                                Work duration (min)
                            </label>
                            <input v-model.number="pomodoro.workMinutes" type="number"
                                   min="1" max="120" :disabled="pomodoro.isRunning"
                                   @change="applySettings"
                                   class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600 disabled:opacity-50" />
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-1.5 block">
                                Break duration (min)
                            </label>
                            <input v-model.number="pomodoro.breakMinutes" type="number"
                                   min="1" max="60" :disabled="pomodoro.isRunning"
                                   @change="applySettings"
                                   class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600 disabled:opacity-50" />
                        </div>
                    </div>
                    <!-- Presets -->
                    <div class="flex gap-2 mt-3">
                        <button v-for="preset in presets" :key="preset.label"
                                @click="applyPreset(preset)"
                                :disabled="pomodoro.isRunning"
                                class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 text-gray-500 dark:text-gray-400 transition-all disabled:opacity-40">
                            {{ preset.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: Stats + History -->
            <div class="space-y-5">

                <!-- XP Progress -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Your Progress</h3>
                    <div class="text-center mb-4">
                        <span class="text-3xl font-bold text-indigo-600">Lv.{{ xpProgress.level }}</span>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ levelTitle }}</p>
                    </div>
                    <div class="w-full h-3 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-2">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all"
                             :style="{ width: xpProgress.percent + '%' }">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 text-center">
                        {{ xpProgress.progress_xp }} / {{ xpProgress.needed_xp }} XP to level {{ xpProgress.next_level }}
                    </p>
                </div>

                <!-- Today's stats -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Stats</h3>
                    <div class="space-y-3">
                        <div v-for="stat in statCards" :key="stat.label"
                             class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ stat.label }}</span>
                            <span class="font-bold text-gray-800 dark:text-gray-100">{{ stat.value }}</span>
                        </div>
                    </div>
                </div>

                <!-- Session history -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100">Recent Sessions</h3>
                    </div>
                    <div v-if="sessions.length === 0"
                         class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                        No sessions yet
                    </div>
                    <div v-else class="divide-y divide-gray-50 dark:divide-gray-800">
                        <div v-for="session in sessions" :key="session.id"
                             class="flex items-center gap-3 px-5 py-3">
                            <div :class="[
                                'w-2 h-2 rounded-full shrink-0',
                                session.status === 'completed' ? 'bg-green-400' : 'bg-gray-300'
                            ]"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ session.sessions_completed }}x {{ session.work_minutes }}min
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ session.habit?.name ?? 'No habit' }}
                                    · {{ formatDate(session.started_at) }}
                                </p>
                            </div>
                            <span class="text-xs text-indigo-500 font-medium shrink-0">
                                +{{ session.sessions_completed * 15 }} XP
                            </span>
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
import { Play, Pause, RotateCcw, SkipForward, Settings2 } from 'lucide-vue-next'
import { usePomodoroStore } from '@/stores/pomodoroStore'

const props = defineProps({
    sessions:    Array,
    stats:       Object,
    habits:      Array,
    xpProgress:  Object,
})

const pomodoro = usePomodoroStore()

const presets = [
    { label: '🍅 Classic 25/5',  workMinutes: 25, breakMinutes: 5  },
    { label: '🔥 Deep 50/10',    workMinutes: 50, breakMinutes: 10 },
    { label: '⚡ Short 15/3',    workMinutes: 15, breakMinutes: 3  },
]

const applyPreset = (preset) => {
    pomodoro.workMinutes  = preset.workMinutes
    pomodoro.breakMinutes = preset.breakMinutes
    applySettings()
}

const modes = [
    { key: 'work',  label: '🍅 Focus' },
    { key: 'break', label: '☕ Break' },
]

const totalSessionsCount = computed(() => {
    return (props.stats?.today_sessions ?? 0) + pomodoro.sessionsCount
})

const circumference = 2 * Math.PI * 45
const totalSeconds  = computed(() =>
    pomodoro.currentMode === 'work'
        ? pomodoro.workMinutes * 60
        : pomodoro.breakMinutes * 60
)
const dashOffset = computed(() =>
    circumference - (pomodoro.timeLeft / totalSeconds.value) * circumference
)

const levelTitle = computed(() => {
    const l = props.xpProgress?.level ?? 1
    if (l >= 50) return '🏆 Legend'
    if (l >= 40) return '💎 Diamond'
    if (l >= 30) return '🥇 Gold'
    if (l >= 20) return '🥈 Silver'
    if (l >= 10) return '🥉 Bronze'
    if (l >= 5)  return '⭐ Rising Star'
    return '🌱 Beginner'
})

const statCards = computed(() => [
    { label: 'Today\'s sessions', value: totalSessionsCount.value },
    { label: 'This week',         value: props.stats?.this_week ?? 0 },
    { label: 'Total sessions',    value: props.stats?.total_sessions ?? 0 },
    { label: 'Total focus time',  value: `${props.stats?.total_minutes ?? 0}m` },
])

const formatDate = (dt) => {
    if (!dt) return ''
    return new Date(dt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// Timer controls
const toggleTimer = () => {
    if (!pomodoro.isRunning)  pomodoro.start()
    else                      pomodoro.pause()
}

const switchMode = (mode) => {
    pomodoro.currentMode = mode
    pomodoro.timeLeft    = mode === 'work'
        ? pomodoro.workMinutes * 60
        : pomodoro.breakMinutes * 60
}

const applySettings = () => {
    if (!pomodoro.isRunning) {
        pomodoro.timeLeft = (pomodoro.currentMode === 'work' ? pomodoro.workMinutes : pomodoro.breakMinutes) * 60
    }
}
</script>
