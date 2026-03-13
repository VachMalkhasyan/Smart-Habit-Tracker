// Base64 encoded chime/bell sound
const ringSoundBase64 = "data:audio/mp3;base64,//uQZAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAAFAAAH0wAEBgYKCg4OExMXFxsbICEhJSUqKi4uMjI2Njs7Pz9DQ0dHS0tPT1NTWFhcXGBgZGRoaGxscHB0dHZ2eXl9fX+BgYODhISEiYmNjY6OkpKUlJaWmZmdnZ+fo6Onp6qrrq6ysrW1uLi7u7+/wcHExMXFycna2t7e4eHk5Ojp6e3t8fH19fn5/Pz///8AAAA8TEFNRTMuMTAwA8IAAAAAABQAwAEAAQQAAAfTBjQRAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA//vQZAAABMIFUQ0woAEYgDogAhgA1qWlNDTzAAW8AOEABGAAAAAIAAAAAA1Z/S/+A/4AA/wBv4A//1+gDwAA4AEgAB/wA//qAP//AAv/AAL/Bf////+AAf//8AAIAAACAAD//wAAH/AA//wAAuAAAAAAAAA//wAAP///+AAEAqgAAAAAAP/m/5/+b/m/5v+b/m/5v+b/m/5/+b/9T///+v//v/+r/v/r/9f/X+v/v//////////////////////////////////////9xERwAABIAAAJAAAIhA8sJABIQABDAAAAAkIAAASQAACIQPLAAEQABDA//uQZAMAA8gBRgQwwAEjADmgAhgAMKAY0IwwAETgDlwAAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/+7BkAIAEMAURBTQAASAAMsACCAAA4AAIAAAQADAAA8AAAgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA//uQZAAABPAFRgQwAAHWAHDAABAAMqAUUEwwAExADjQAAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/+7BkAAAEAAVHDQQAASIAXEAABgAD/AFHRDQAAXYAnIAAYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/==";

const APP_URL = 'http://localhost:8000';

async function setupOffscreenDocument() {
    const offscreenUrl = chrome.runtime.getURL('offscreen.html');
    const existingContexts = await chrome.runtime.getContexts({
        contextTypes: ['OFFSCREEN_DOCUMENT'],
        documentUrls: [offscreenUrl]
    });

    if (existingContexts.length > 0) {
        return;
    }

    await chrome.offscreen.createDocument({
        url: 'offscreen.html',
        reasons: ['AUDIO_PLAYBACK'],
        justification: 'Play Pomodoro timer completion sound'
    });
}

function playNotificationSound() {
    setupOffscreenDocument().then(() => {
        chrome.runtime.sendMessage({
            type: 'play_audio',
            target: 'offscreen',
            data: { base64: ringSoundBase64 }
        });
    }).catch(e => console.error("Error setting up offscreen doc:", e));
}

async function syncPomodoroToApp(payload) {
    const data = await chrome.storage.local.get('authToken');
    if (!data.authToken) return;

    try {
        await fetch(`${APP_URL}/api/extension/pomodoro`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${data.authToken}`,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload)
        });
    } catch (e) {
        console.error("Error syncing Pomodoro:", e);
    }
}

chrome.alarms.onAlarm.addListener((alarm) => {
    if (alarm.name === 'pomodoro_alarm') {
        chrome.storage.local.get(['currentMode', 'sessionCount', 'workMin', 'breakMin'], (data) => {
            const mode = data.currentMode ?? 'work';
            const sessionCount = data.sessionCount ?? 0;
            
            // Notification and Sound
            chrome.notifications.create({
                type: 'basic',
                iconUrl: 'icons/icon128.png',
                title: 'Smart Habit Tracker',
                message: mode === 'work' ? '🍅 Focus session done! Time for a break.' : '☕ Break over! Back to focus.',
            });
            playNotificationSound();

            let nextMode, nextSeconds, nextCount = sessionCount;
            
            if (mode === 'work') {
                nextCount++;
                nextMode = 'break';
                nextSeconds = (data.breakMin ?? 5) * 60;
                
                // Track backend sync
                syncPomodoroToApp({
                    work_minutes: data.workMin ?? 25,
                    break_minutes: data.breakMin ?? 5,
                    sessions_completed: 1,
                    total_minutes: data.workMin ?? 25
                });
                
            } else {
                nextMode = 'work';
                nextSeconds = (data.workMin ?? 25) * 60;
            }

            chrome.storage.local.set({
                timerRunning: false,
                currentMode: nextMode,
                timerSeconds: nextSeconds,
                sessionCount: nextCount,
                timerEndTime: 0
            });
        });
    }
});

chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
    if (msg.type === 'START_TIMER') {
        const seconds = msg.seconds;
        const endTime = Date.now() + (seconds * 1000);
        
        chrome.storage.local.set({ 
            timerRunning: true, 
            timerEndTime: endTime,
            timerSeconds: seconds 
        });
        
        chrome.alarms.create('pomodoro_alarm', { when: endTime });
        sendResponse({ ok: true });
    }

    if (msg.type === 'STOP_TIMER') {
        chrome.alarms.clear('pomodoro_alarm');
        chrome.storage.local.set({ timerRunning: false, timerEndTime: 0 });
        sendResponse({ ok: true });
    }
});
