<template>
    <div class="bg-indigo-600 dark:bg-indigo-900 rounded-2xl shadow-sm p-6 text-white h-full flex flex-col items-center justify-center text-center relative overflow-hidden group" :class="pomodoro.modeColor">
        <h2 class="font-bold text-lg mb-2 relative z-10 flex items-center gap-2">
            {{ pomodoro.modeLabel }}
        </h2>
        
        <div class="text-5xl font-extrabold mb-5 relative z-10 tabular-nums tracking-tight drop-shadow-sm">
            {{ pomodoro.formattedTime }}
        </div>
        
        <div class="flex gap-3 relative z-10">
            <button v-if="!pomodoro.isRunning" @click="pomodoro.start()" class="px-5 py-2 bg-white text-indigo-600 font-semibold rounded-full hover:bg-gray-50 transition-colors shadow-sm">
                Start
            </button>
            <button v-if="pomodoro.isRunning" @click="pomodoro.pause()" class="px-5 py-2 bg-white/20 text-white font-semibold rounded-full hover:bg-white/30 transition-colors backdrop-blur-sm">
                Pause
            </button>
            <button v-if="showReset" @click="pomodoro.reset()" class="px-5 py-2 bg-transparent border border-white/50 text-white font-semibold rounded-full hover:bg-white/10 transition-colors">
                Reset
            </button>
        </div>

        <!-- Decorative background elements -->
        <div class="absolute -right-6 -bottom-6 w-32 h-32 border-4 border-white/10 rounded-full z-0 group-hover:scale-110 transition-transform duration-500"></div>
        <div class="absolute top-4 left-4 w-16 h-16 border-2 border-white/5 rounded-full z-0"></div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePomodoroStore } from '@/stores/pomodoroStore'

const pomodoro = usePomodoroStore()

const showReset = computed(() => {
    const targetMinutes = pomodoro.currentMode === 'work' ? pomodoro.workMinutes : pomodoro.breakMinutes
    return pomodoro.timeLeft !== targetMinutes * 60
})
</script>
