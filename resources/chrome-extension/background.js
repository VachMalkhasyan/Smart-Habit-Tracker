let timerInterval = null

chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
    if (msg.type === 'START_TIMER') {
        let seconds = msg.seconds
        timerInterval = setInterval(() => {
            seconds--
            chrome.storage.local.set({ timerSeconds: seconds })
            if (seconds <= 0) {
                clearInterval(timerInterval)
                chrome.storage.local.set({ timerRunning: false, timerSeconds: 0 })
                chrome.notifications.create({
                    type:    'basic',
                    iconUrl: 'icons/icon128.png',
                    title:   'Smart Habit Tracker',
                    message: msg.mode === 'work' ? '🍅 Focus session done! Time for a break.' : '☕ Break over! Back to focus.',
                })
            }
        }, 1000)
        sendResponse({ ok: true })
    }

    if (msg.type === 'STOP_TIMER') {
        clearInterval(timerInterval)
        sendResponse({ ok: true })
    }
})
