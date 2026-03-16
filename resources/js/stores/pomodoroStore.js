import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const usePomodoroStore = defineStore('pomodoro', () => {
    const isRunning       = ref(false)
    const timeLeft        = ref(25 * 60)
    const currentMode     = ref('work')
    const workMinutes     = ref(25)
    const breakMinutes    = ref(5)
    const sessionsCount   = ref(0)
    const sessionId       = ref(null)
    const selectedHabit   = ref(null)
    let   interval        = null

    const formattedTime = computed(() => {
        const m = Math.floor(timeLeft.value / 60).toString().padStart(2, '0')
        const s = (Math.floor(timeLeft.value) % 60).toString().padStart(2, '0')
        return `${m}:${s}`
    })

    const modeLabel = computed(() => 
        currentMode.value === 'work' ? '🍅 Focus' : '☕ Break'
    )

    const modeColor = computed(() => 
        currentMode.value === 'work' ? 'bg-indigo-600' : 'bg-green-600'
    )

    const resumeIfRunning = () => {
        if (isRunning.value && timeLeft.value > 0) {
            if (interval) clearInterval(interval)
            interval = setInterval(tick, 1000)
        }
    }

    const start = async () => {
        if (!sessionId.value) {
            try {
                const { data } = await axios.post('/pomodoro', {
                    work_minutes:  workMinutes.value,
                    break_minutes: breakMinutes.value,
                    habit_id:      selectedHabit.value || null,
                })
                sessionId.value = data.id
            } catch (e) {
                console.error("Failed to start session on backend", e)
            }
        }
        isRunning.value = true
        clearInterval(interval) // ensure no duplicates
        interval = setInterval(tick, 1000)
    }

    const pause = () => {
        isRunning.value = false
        clearInterval(interval)
    }

    const tick = () => {
        if (timeLeft.value <= 0) {
            sessionComplete()
        } else {
            timeLeft.value--
        }
    }

    const sessionComplete = async () => {
        clearInterval(interval)
        isRunning.value = false

        if (currentMode.value === 'work') {
            sessionsCount.value++
            if (sessionId.value) {
                try {
                    await axios.post(`/pomodoro/${sessionId.value}/complete`, {
                        sessions_completed: 1,
                        total_minutes: workMinutes.value,
                    })
                    sessionId.value = null
                } catch (e) {
                    console.error("Failed to complete session on backend", e)
                }
            }

            // Play sound if possible
            new Audio('https://www.soundjay.com/buttons/beep-07.wav').play().catch(() => {})

            // Switch mode to break
            currentMode.value = 'break'
            timeLeft.value    = breakMinutes.value * 60
        } else {
            // Switch mode back to work
            currentMode.value = 'work'
            timeLeft.value    = workMinutes.value * 60
        }
    }

    const reset = async () => {
        pause()
        const targetMinutes = currentMode.value === 'work' ? workMinutes.value : breakMinutes.value
        timeLeft.value = targetMinutes * 60
        if (sessionId.value) {
            try {
                await axios.post(`/pomodoro/${sessionId.value}/abandon`)
            } catch (e) {
                console.error("Failed to abandon session", e)
            }
            sessionId.value = null
        }
    }

    return {
        isRunning, timeLeft, currentMode, workMinutes,
        breakMinutes, sessionsCount, sessionId, selectedHabit,
        formattedTime, modeLabel, modeColor,
        start, pause, reset, sessionComplete, resumeIfRunning
    }
}, { 
    persist: {
        key: 'growthzone-pomodoro',
        paths: [
            'isRunning', 'timeLeft', 'currentMode',
            'workMinutes', 'breakMinutes', 'sessionsCount',
            'sessionId', 'selectedHabit'
        ]
    } 
})
