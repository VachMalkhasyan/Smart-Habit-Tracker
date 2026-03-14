<template>
    <div class="bg-indigo-600 dark:bg-indigo-900 rounded-2xl shadow-sm p-6 text-white h-full flex flex-col items-center justify-center text-center relative overflow-hidden group">
        <h2 class="font-bold text-lg mb-2 relative z-10 flex items-center gap-2">
            🍅 Quick Pomodoro
        </h2>
        
        <div class="text-5xl font-extrabold mb-5 relative z-10 tabular-nums tracking-tight drop-shadow-sm">
            {{ formattedTime }}
        </div>
        
        <div class="flex gap-3 relative z-10">
            <button v-if="!isRunning" @click="startTimer" class="px-5 py-2 bg-white text-indigo-600 font-semibold rounded-full hover:bg-gray-50 transition-colors shadow-sm">
                Start
            </button>
            <button v-if="isRunning" @click="pauseTimer" class="px-5 py-2 bg-white/20 text-white font-semibold rounded-full hover:bg-white/30 transition-colors backdrop-blur-sm">
                Pause
            </button>
            <button v-if="timeLeft !== 25 * 60" @click="resetTimer" class="px-5 py-2 bg-transparent border border-white/50 text-white font-semibold rounded-full hover:bg-white/10 transition-colors">
                Reset
            </button>
        </div>

        <!-- Decorative background elements -->
        <div class="absolute -right-6 -bottom-6 w-32 h-32 border-4 border-white/10 rounded-full z-0 group-hover:scale-110 transition-transform duration-500"></div>
        <div class="absolute top-4 left-4 w-16 h-16 border-2 border-white/5 rounded-full z-0"></div>
    </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'

const WORK_TIME = 25 * 60
const timeLeft = ref(WORK_TIME)
const isRunning = ref(false)
let timer = null

const formattedTime = computed(() => {
    const m = Math.floor(timeLeft.value / 60).toString().padStart(2, '0')
    const s = (timeLeft.value % 60).toString().padStart(2, '0')
    return `${m}:${s}`
})

const startTimer = () => {
    if (isRunning.value) return
    isRunning.value = true
    timer = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--
        } else {
            resetTimer()
            const audio = new Audio()
            audio.src = 'data:audio/mp3;base64,//uQZAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAAFAAAH0wAEBgYKCg4OExMXFxsbICEhJSUqKi4uMjI2Njs7Pz9DQ0dHS0tPT1NTWFhcXGBgZGRoaGxscHB0dHZ2eXl9fX+BgYODhISEiYmNjY6OkpKUlJaWmZmdnZ+fo6Onp6qrrq6ysrW1uLi7u7+/wcHExMXFycna2t7e4eHk5Ojp6e3t8fH19fn5/Pz///8AAAA8TEFNRTMuMTAwA8IAAAAAABQAwAEAAQQAAAfTBjQRAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA//vQZAAABMIFUQ0woAEYgDogAhgA1qWlNDTzAAW8AOEABGAAAAAIAAAAAA1Z/S/+A/4AA/wBv4A//1+gDwAA4AEgAB/wA//qAP//AAv/AAL/Bf////+AAf//8AAIAAACAAD//wAAH/AA//wAAuAAAAAAAAA//wAAP///+AAEAqgAAAAAAP/m/5/+b/m/5v+b/m/5v+b/m/5/+b/9T///+v//v/+r/v/r/9f/X+v/v//////////////////////////////////////9xERwAABIAAAJAAAIhA8sJABIQABDAAAAAkIAAASQAACIQPLAAEQABDA//uQZAMAA8gBRgQwwAEjADmgAhgAMKAY0IwwAETgDlwAAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/+7BkAIAEMAURBTQAASAAMsACCAAA4AAIAAAQADAAA8AAAgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA//uQZAAABPAFRgQwAAHWAHDAABAAMqAUUEwwAExADjQAAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/+7BkAAAEAAVHDQQAASIAXEAABgAD/AFHRDQAAXYAnIAAYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/=='
            audio.play().catch(() => {})
        }
    }, 1000)
}

const pauseTimer = () => {
    isRunning.value = false
    clearInterval(timer)
}

const resetTimer = () => {
    pauseTimer()
    timeLeft.value = WORK_TIME
}

onUnmounted(() => {
    if (timer) clearInterval(timer)
})
</script>
