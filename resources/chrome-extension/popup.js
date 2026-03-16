const APP_URL = 'http://localhost:8000'

let timerRunning     = false
let timerSeconds     = 25 * 60
let currentMode      = 'work'
let workMin          = 25
let breakMin         = 5
let sessionCount     = 0
let timerInterval    = null
let authToken        = null

// DOM refs
const notLoggedIn   = document.getElementById('not-logged-in')
const mainContent   = document.getElementById('main-content')
const xpBarWrap     = document.getElementById('xp-bar-wrap')
const timerTime     = document.getElementById('timer-time')
const timerMode     = document.getElementById('timer-mode')
const btnToggle     = document.getElementById('btn-toggle')
const btnReset      = document.getElementById('btn-reset')
const btnSkip       = document.getElementById('btn-skip')
const sessionDots   = document.getElementById('session-dots')
const workInput     = document.getElementById('work-min')
const breakInput    = document.getElementById('break-min')
const habitsList    = document.getElementById('habits-list')
const xpFill        = document.getElementById('xp-fill')
const xpText        = document.getElementById('xp-text')
const xpLevel       = document.getElementById('xp-level')
const levelBadge    = document.getElementById('level-badge')
const loginEmail    = document.getElementById('login-email')
const loginPass     = document.getElementById('login-password')
const btnLogin      = document.getElementById('btn-login')
const loginError    = document.getElementById('login-error')
const noteContainer = document.getElementById('dashboard-note-container')
const noteText      = document.getElementById('dashboard-note-text')
const notifBadge    = document.getElementById('notif-badge')
const notifSection  = document.getElementById('notifications-section')
const notifList     = document.getElementById('notif-list')
const notifCount    = document.getElementById('notif-count-label')

// Init
document.addEventListener('DOMContentLoaded', async () => {
    // Restore timer state
    chrome.storage.local.get(
        ['timerSeconds','timerEndTime','timerRunning','currentMode','workMin','breakMin','sessionCount','authToken'],
        async (data) => {
            if (data.authToken)    authToken    = data.authToken
            
            if (data.timerRunning && data.timerEndTime && data.timerEndTime > Date.now()) {
                timerSeconds = Math.ceil((data.timerEndTime - Date.now()) / 1000)
            } else {
                if (data.timerSeconds !== undefined) timerSeconds = data.timerSeconds
            }
            if (data.workMin)    { workMin  = data.workMin;  workInput.value  = workMin  }
            if (data.breakMin)   { breakMin = data.breakMin; breakInput.value = breakMin }
            if (data.sessionCount) sessionCount = data.sessionCount
            currentMode = data.currentMode ?? 'work'
            
            updateTimerDisplay()
            updateDots()
            
            if (data.timerRunning && timerSeconds > 0) {
                startLocalInterval(false)
            } else if (data.timerRunning && timerSeconds <= 0) {
                // Background alarm already finished it while popup was closed
                fetchLatestState()
            }
            
            if (authToken) {
                await syncWithApp()
            }
        }
    )

    await checkAuth()
    setupTabs()
    setupTimerEvents()
    setupLoginForm()
})

async function syncWithApp() {
    try {
        const res  = await apiGet('/api/extension/pomodoro')
        const data = await res.json()

        if (data.has_active_session && data.session) {
            const session = data.session

            workMin      = session.work_minutes
            breakMin     = session.break_minutes
            timerSeconds = session.remaining_seconds
            currentMode  = 'work'

            chrome.storage.local.set({
                workMin, breakMin, timerSeconds, currentMode
            })

            updateTimerDisplay()
            
            // Auto-start extension timer if an active session is found
            if (!timerRunning) {
                startLocalInterval(false)
            }

            const banner = document.getElementById('sync-banner')
            if (banner) {
                banner.textContent = `⚡ Synced with app${session.habit ? ' — ' + session.habit : ''}`
                banner.classList.remove('hidden')
                setTimeout(() => banner.classList.add('hidden'), 3000)
            }
        }
    } catch (e) {
        console.log('Sync failed', e)
    }
}

// Auth check
async function checkAuth() {
    chrome.storage.local.get('authToken', async (data) => {
        if (!data.authToken) {
            showLogin()
            return
        }
        authToken = data.authToken
        await loadUser()
    })
}

function showLogin() {
    notLoggedIn.classList.remove('hidden')
    mainContent.classList.add('hidden')
    xpBarWrap.classList.add('hidden')
}

function showMain() {
    notLoggedIn.classList.add('hidden')
    mainContent.classList.remove('hidden')
    xpBarWrap.classList.remove('hidden')
}

// Login form
function setupLoginForm() {
    btnLogin.addEventListener('click', async () => {
        const email    = loginEmail.value.trim()
        const password = loginPass.value

        if (!email || !password) {
            showError('Please enter email and password')
            return
        }

        btnLogin.textContent = 'Connecting...'
        btnLogin.disabled    = true
        loginError.classList.add('hidden')

        try {
            const res = await fetch(`${APP_URL}/api/extension/token`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ email, password }),
            })

            const data = await res.json()

            if (!res.ok) {
                showError(data.message ?? 'Invalid credentials')
                return
            }

            // Save token
            authToken = data.token
            chrome.storage.local.set({ authToken })

            loginEmail.value = ''
            loginPass.value  = ''

            await loadUser()

        } catch (e) {
            showError('Could not connect to app. Is it running?')
        } finally {
            btnLogin.textContent = 'Connect Account'
            btnLogin.disabled    = false
        }
    })

    // Allow Enter key
    loginPass.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') btnLogin.click()
    })
}

function showError(msg) {
    loginError.textContent = msg
    loginError.classList.remove('hidden')
}

// Load user data
async function loadUser() {
    try {
        const res = await apiGet('/api/extension/me')

        if (!res.ok) {
            // Token expired
            chrome.storage.local.remove('authToken')
            authToken = null
            showLogin()
            return
        }

        const data = await res.json()
        showMain()

        levelBadge.textContent = `Lv.${data.level}`
        xpLevel.textContent    = `Lv.${data.level}`
        xpText.textContent     = `${data.xp_progress.progress_xp}/${data.xp_progress.needed_xp} XP`
        xpFill.style.width     = `${data.xp_progress.percent}%`

        if (data.dashboard_note) {
            noteContainer.classList.remove('hidden');
            noteText.textContent = data.dashboard_note;
        } else {
            noteContainer.classList.add('hidden');
            noteText.textContent = '';
        }

        loadHabits(data.habits)
        renderNotifications(data.unread_notifications ?? [], data.unread_count ?? 0)

    } catch (e) {
        showLogin()
    }
}

function renderNotifications(notifications, count) {
    if (!notifBadge || !notifSection || !notifList || !notifCount) return

    // Update badge on Habits tab
    if (count > 0) {
        notifBadge.textContent = count > 9 ? '9+' : count
        notifBadge.classList.remove('hidden')
    } else {
        notifBadge.classList.add('hidden')
    }
    notifCount.textContent = count

    // Show/hide section
    if (notifications.length === 0) {
        notifSection.classList.add('hidden')
        return
    }
    notifSection.classList.remove('hidden')

    // Render compact rows
    notifList.innerHTML = notifications.map(n => `
        <div style="display:flex; align-items:flex-start; gap:8px; padding:8px; background:#f9fafb; border-radius:8px; border-left:3px solid #6366f1;">
            <span style="font-size:18px; line-height:1;">${n.icon}</span>
            <div style="flex:1; min-width:0;">
                <div style="font-size:12px; font-weight:700; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${n.title}</div>
                <div style="font-size:11px; color:#6b7280; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${n.message}</div>
            </div>
        </div>
    `).join('')

    // Mark all read button
    const btnMarkAll = document.getElementById('btn-mark-all-read')
    if (btnMarkAll) {
        btnMarkAll.onclick = async () => {
            try {
                await apiPost('/api/extension/notifications/read-all')
                notifBadge.classList.add('hidden')
                notifCount.textContent = '0'
                notifSection.classList.add('hidden')
            } catch (e) {
                console.error('Mark all read failed', e)
            }
        }
    }
}

function loadHabits(habits) {
    document.getElementById('habits-loading').classList.add('hidden')

    if (!habits || habits.length === 0) {
        habitsList.innerHTML = '<p class="muted text-center" style="padding:16px 0">No active habits today 🎯</p>'
        return
    }

    habitsList.innerHTML = habits.map(h => `
        <div class="habit-item" data-id="${h.id}">
            <div class="habit-check ${h.completed_today ? 'done' : ''}" data-id="${h.id}">
                ${h.completed_today ? '✓' : ''}
            </div>
            <span class="habit-name">${h.name}</span>
            <span class="habit-streak">🔥${h.current_streak}</span>
        </div>
    `).join('')

    // Attach event listeners to comply with CSP
    habitsList.querySelectorAll('.habit-check').forEach(el => {
        el.addEventListener('click', () => {
            toggleHabit(el.dataset.id, el)
        })
    })
}

const toggleHabit = async (habitId, el) => {
    try {
        const res  = await apiPost(`/api/extension/habits/${habitId}/toggle`)
        const data = await res.json()

        el.classList.toggle('done', data.is_done)
        el.innerHTML = data.is_done ? '✓' : ''

        if (data.xp_progress) {
            xpFill.style.width     = `${data.xp_progress.percent}%`
            xpText.textContent     = `${data.xp_progress.progress_xp}/${data.xp_progress.needed_xp} XP`
            xpLevel.textContent    = `Lv.${data.xp_progress.level}`
            levelBadge.textContent = `Lv.${data.xp_progress.level}`
        }
    } catch (e) {
        console.error('Toggle failed', e)
    }
}

// API helpers
function apiGet(path) {
    return fetch(`${APP_URL}${path}`, {
        headers: {
            'Accept':        'application/json',
            'Authorization': `Bearer ${authToken}`,
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
}

function apiPost(path, body = {}) {
    return fetch(`${APP_URL}${path}`, {
        method:  'POST',
        headers: {
            'Content-Type':  'application/json',
            'Accept':        'application/json',
            'Authorization': `Bearer ${authToken}`,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    })
}

// Timer
function startLocalInterval(notifyBackground = true) {
    timerRunning          = true
    btnToggle.textContent = '⏸'
    
    if (notifyBackground) {
        chrome.runtime.sendMessage({ type: 'START_TIMER', seconds: timerSeconds })
    }
    
    timerInterval = setInterval(() => {
        timerSeconds--
        updateTimerDisplay()
        if (timerSeconds <= 0) {
            clearInterval(timerInterval)
            timerInterval = null
            
            // Allow background.js 1 second to fire the alarm, sync, and update storage
            setTimeout(fetchLatestState, 1000)
        }
    }, 1000)
}

function fetchLatestState() {
    chrome.storage.local.get(['timerSeconds','timerRunning','currentMode','sessionCount'], (data) => {
        timerRunning = data.timerRunning || false
        btnToggle.textContent = timerRunning ? '⏸' : '▶'
        if (data.timerSeconds !== undefined) timerSeconds = data.timerSeconds
        if (data.sessionCount !== undefined) sessionCount = data.sessionCount
        if (data.currentMode !== undefined)  currentMode = data.currentMode
        updateDots()
        updateTimerDisplay()
    })
}

function stopLocalInterval() {
    timerRunning          = false
    btnToggle.textContent = '▶'
    clearInterval(timerInterval)
    timerInterval = null
    chrome.runtime.sendMessage({ type: 'STOP_TIMER' })
}

function sessionDone() {
    // Fired on 'skip' manual override
    timerRunning          = false
    btnToggle.textContent = '▶'

    if (currentMode === 'work') {
        sessionCount++
        chrome.storage.local.set({ sessionCount })
        updateDots()
        currentMode  = 'break'
        timerSeconds = breakMin * 60
    } else {
        currentMode  = 'work'
        timerSeconds = workMin * 60
    }

    chrome.storage.local.set({ currentMode, timerSeconds, timerRunning: false, timerEndTime: 0 })
    updateTimerDisplay()
    chrome.runtime.sendMessage({ type: 'STOP_TIMER' })
}

function updateTimerDisplay() {
    const m = Math.floor(timerSeconds / 60).toString().padStart(2, '0')
    const s = Math.floor(timerSeconds % 60).toString().padStart(2, '0')
    timerTime.textContent = `${m}:${s}`
    timerMode.textContent = currentMode === 'work' ? 'Focus time 🍅' : 'Break time ☕'
}

function updateDots() {
    const filled = sessionCount % 4
    sessionDots.textContent = Array.from(
        { length: 4 }, (_, i) => i < filled ? '●' : '○'
    ).join(' ')
}

function setupTimerEvents() {
    btnToggle.addEventListener('click', () => {
        if (timerRunning) stopLocalInterval()
        else startLocalInterval()
    })

    btnReset.addEventListener('click', () => {
        stopLocalInterval()
        timerSeconds = (currentMode === 'work' ? workMin : breakMin) * 60
        updateTimerDisplay()
        chrome.storage.local.set({ timerSeconds })
    })

    btnSkip.addEventListener('click', () => {
        stopLocalInterval()
        sessionDone()
    })

    workInput.addEventListener('change', () => {
        workMin = parseInt(workInput.value) || 25
        if (!timerRunning && currentMode === 'work') {
            timerSeconds = workMin * 60
            updateTimerDisplay()
        }
        chrome.storage.local.set({ workMin })
    })

    breakInput.addEventListener('change', () => {
        breakMin = parseInt(breakInput.value) || 5
        if (!timerRunning && currentMode === 'break') {
            timerSeconds = breakMin * 60
            updateTimerDisplay()
        }
        chrome.storage.local.set({ breakMin })
    })
}

function setupTabs() {
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'))
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'))
            tab.classList.add('active')
            document.getElementById(`tab-${tab.dataset.tab}`).classList.remove('hidden')

            // Load habits when switching to habits tab
            if (tab.dataset.tab === 'habits') loadUser()
        })
    })
}

async function loadJobs() {
    document.getElementById('jobs-loading').classList.remove('hidden')
    try {
        const res  = await apiGet('/api/extension/jobs')
        const data = await res.json()

        document.getElementById('jobs-loading').classList.add('hidden')
        document.getElementById('jobs-stats').classList.remove('hidden')

        document.getElementById('jobs-applied').textContent =
            `${data.stats.applied} Applied`
        document.getElementById('jobs-interviewing').textContent =
            `${data.stats.interviewing} Interviews`
        document.getElementById('jobs-offers').textContent =
            `${data.stats.offers} Offers`

        const container = document.getElementById('upcoming-interviews')
        if (data.upcoming_interviews.length === 0) {
            container.innerHTML =
                '<p class="muted text-center" style="padding:8px 0">No upcoming interviews</p>'
        } else {
            container.innerHTML = data.upcoming_interviews.map(i => `
                <div class="habit-item">
                    <div class="habit-check done">🗓️</div>
                    <div style="flex:1">
                        <p class="habit-name">${i.type} — ${i.company}</p>
                        <p class="muted">${new Date(i.scheduled_at)
                            .toLocaleDateString('en-US', {
                                weekday: 'short', month: 'short',
                                day: 'numeric', hour: '2-digit', minute: '2-digit'
                            })}</p>
                    </div>
                    ${i.has_prep ? '<span style="color:#4f46e5;font-size:11px">AI Ready ✓</span>' : ''}
                </div>
            `).join('')
        }
    } catch (e) {
        document.getElementById('jobs-loading').textContent = 'Failed to load jobs'
    }
}

// Call loadJobs when jobs tab is clicked
document.querySelector('[data-tab="jobs"]')
    .addEventListener('click', loadJobs)

