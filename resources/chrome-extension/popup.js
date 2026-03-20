// State
let authToken = null;
let activeTab = 'jobs';
let habits = [];
let userData = null;

const APP_URL = 'http://localhost:8000';

// DOM Elements
const elNotLoggedIn = document.getElementById('not-logged-in');
const elMainContent = document.getElementById('main-content');
const elLoginEmail = document.getElementById('login-email');
const elLoginPass = document.getElementById('login-password');
const elBtnLogin = document.getElementById('btn-login');
const elLoginError = document.getElementById('login-error');

const elTabs = document.querySelectorAll('.tab');
const elTabContents = document.querySelectorAll('.tab-content');

const elHabitsList = document.getElementById('habits-list');
const elHabitsLoading = document.getElementById('habits-loading');

const elJobsLoading = document.getElementById('jobs-loading');
const elJobsStats = document.getElementById('jobs-stats');
const elJobsApplied = document.getElementById('jobs-applied');
const elJobsInterviewing = document.getElementById('jobs-interviewing');
const elJobsOffers = document.getElementById('jobs-offers');
const elUpcomingInterviews = document.getElementById('upcoming-interviews');

const elXpBar = document.getElementById('xp-bar-wrap');
const elXpLevel = document.getElementById('xp-level');
const elXpText = document.getElementById('xp-text');
const elXpFill = document.getElementById('xp-fill');

const elBtnStartScan = document.getElementById('btn-start-scan');
const elScanStatus = document.getElementById('scan-status');
const elScanResults = document.getElementById('scan-results');
const elScanCompany = document.getElementById('scan-company');
const elScanRole = document.getElementById('scan-role');
const elScanUrl = document.getElementById('scan-url');
const elBtnSaveJob = document.getElementById('btn-save-job');

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    chrome.storage.local.get(['authToken'], (result) => {
        if (result.authToken) {
            authToken = result.authToken;
            showMainContent();
        } else {
            showLogin();
        }
    });
});

// Auth
elBtnLogin.addEventListener('click', async () => {
    const email = elLoginEmail.value;
    const password = elLoginPass.value;

    if (!email || !password) return;

    elBtnLogin.disabled = true;
    elBtnLogin.innerText = 'Connecting...';
    elLoginError.classList.add('hidden');

    try {
        const response = await fetch(`${APP_URL}/api/extension/token`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok) {
            authToken = data.token;
            chrome.storage.local.set({ authToken }, () => {
                showMainContent();
            });
        } else {
            elLoginError.innerText = data.message || 'Login failed';
            elLoginError.classList.remove('hidden');
        }
    } catch (err) {
        elLoginError.innerText = 'Could not connect to server';
        elLoginError.classList.remove('hidden');
    } finally {
        elBtnLogin.disabled = false;
        elBtnLogin.innerText = 'Connect Account';
    }
});

function showLogin() {
    elNotLoggedIn.classList.remove('hidden');
    elMainContent.classList.add('hidden');
    elXpBar.classList.add('hidden');
}

function showMainContent() {
    elNotLoggedIn.classList.add('hidden');
    elMainContent.classList.remove('hidden');
    elXpBar.classList.remove('hidden');
    loadUser(); // Loads habits and stats
    loadJobs();
}

// Tabs
elTabs.forEach(tab => {
    tab.addEventListener('click', () => {
        const target = tab.getAttribute('data-tab');
        switchTab(target);
    });
});

function switchTab(tabId) {
    activeTab = tabId;
    elTabs.forEach(t => {
        t.classList.toggle('active', t.getAttribute('data-tab') === tabId);
    });
    elTabContents.forEach(c => {
        c.classList.toggle('hidden', c.id !== `tab-${tabId}`);
    });

    if (tabId === 'habits') loadUser();
    if (tabId === 'jobs') loadJobs();
}

// User Stats & Habits
async function loadUser() {
    try {
        const response = await fetch(`${APP_URL}/api/extension/me`, {
            headers: { 
                'Authorization': `Bearer ${authToken}`,
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        if (response.ok) {
            userData = data;
            updateXpBar();
            renderHabits(data.habits || []);
        } else if (response.status === 401) {
            chrome.storage.local.remove('authToken');
            showLogin();
        }
    } catch (err) {}
}

function updateXpBar() {
    if (!userData || !userData.xp_progress) return;
    elXpLevel.innerText = `Lv.${userData.level}`;
    const progress = userData.xp_progress;
    elXpText.innerText = `${progress.progress_xp}/${progress.needed_xp} XP`;
    elXpFill.style.width = `${progress.percent}%`;
}

function renderHabits(habitsList) {
    elHabitsLoading.classList.add('hidden');
    if (habitsList.length === 0) {
        elHabitsList.innerHTML = '<div class="muted text-center" style="padding:16px 0">No habits for today</div>';
        return;
    }

    elHabitsList.innerHTML = habitsList.map(habit => `
        <div class="habit-item">
            <div class="habit-info" style="flex:1">
                <div class="habit-name" style="font-weight:600; font-size:13px;">${habit.name}</div>
                <div class="habit-meta" style="font-size:11px; color:#6b7280;">${habit.category || 'General'} • ${habit.current_streak} day streak</div>
            </div>
            <button class="btn-check ${habit.completed_today ? 'checked' : ''}" 
                    style="width:28px; height:28px; border-radius:6px; border:1px solid #e5e7eb; background:#fff; cursor:pointer;"
                    data-id="${habit.id}">
                ${habit.completed_today ? '✓' : ''}
            </button>
        </div>
    `).join('');

    elHabitsList.querySelectorAll('.btn-check').forEach(btn => {
        btn.onclick = () => toggleHabit(btn.getAttribute('data-id'), btn);
    });
}

async function toggleHabit(id, btn) {
    try {
        const response = await fetch(`${APP_URL}/api/extension/habits/${id}/toggle`, {
            method: 'POST',
            headers: { 
                'Authorization': `Bearer ${authToken}`,
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        if (response.ok) {
            btn.classList.toggle('checked', data.is_done);
            btn.innerHTML = data.is_done ? '✓' : '';
            if (data.xp_progress) {
                userData.xp_progress = data.xp_progress;
                userData.level = data.xp_progress.level;
                updateXpBar();
            }
        }
    } catch (err) {}
}

// Jobs
async function loadJobs() {
    elJobsLoading.classList.remove('hidden');
    try {
        const response = await fetch(`${APP_URL}/api/extension/jobs`, {
            headers: { 
                'Authorization': `Bearer ${authToken}`,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();
        if (response.ok) {
            elJobsApplied.innerText = `${data.stats.applied} Applied`;
            elJobsInterviewing.innerText = `${data.stats.interviewing} Interviews`;
            elJobsOffers.innerText = `${data.stats.offers} Offers`;
            elJobsStats.classList.remove('hidden');

            if (data.upcoming_interviews && data.upcoming_interviews.length > 0) {
                elUpcomingInterviews.innerHTML = '<div style="font-size:11px; font-weight:700; color:#374151; margin:12px 0 8px;">Upcoming Interviews</div>' + 
                    data.upcoming_interviews.map(iv => `
                        <div style="background:#f3f4f6; border-radius:6px; padding:8px; margin-bottom:6px;">
                            <div style="font-size:12px; font-weight:600;">${iv.company}</div>
                            <div style="font-size:10px; color:#6b7280;">${new Date(iv.scheduled_at).toLocaleDateString()}</div>
                        </div>
                    `).join('');
            } else {
                elUpcomingInterviews.innerHTML = '';
            }
        }
    } catch (err) {
    } finally {
        elJobsLoading.classList.add('hidden');
    }
}

// Job Scanner
elBtnStartScan.addEventListener('click', async () => {
    elScanStatus.innerText = 'Scanning page...';
    elScanStatus.classList.remove('hidden');
    elScanResults.classList.add('hidden');
    elBtnStartScan.disabled = true;

    try {
        const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
        
        const results = await chrome.scripting.executeScript({
            target: { tabId: tab.id },
            files: ['scanner.js']
        });

        if (results && results[0] && results[0].result) {
            const data = results[0].result;
            elScanCompany.value = data.company || '';
            elScanRole.value = data.role || '';
            elScanUrl.value = data.url || '';
            
            elScanResults.classList.remove('hidden');
            elScanStatus.classList.add('hidden');
        } else {
            elScanStatus.innerText = 'No job data found on this page.';
        }
    } catch (err) {
        elScanStatus.innerText = 'Scan failed: ' + err.message;
    } finally {
        elBtnStartScan.disabled = false;
    }
});

elBtnSaveJob.addEventListener('click', async () => {
    const jobData = {
        company_name: elScanCompany.value,
        role_title: elScanRole.value,
        job_url: elScanUrl.value,
        status: 'wishlist',
        priority: 2
    };

    if (!jobData.company_name || !jobData.role_title) {
        alert('Company and Role are required');
        return;
    }

    elBtnSaveJob.disabled = true;
    elBtnSaveJob.innerText = 'Saving...';

    try {
        const response = await fetch(`${APP_URL}/api/extension/jobs`, {
            method: 'POST',
            headers: { 
                'Authorization': `Bearer ${authToken}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(jobData)
        });

        if (response.ok) {
            elScanStatus.innerText = 'Job saved successfully! 💼';
            elScanStatus.classList.remove('hidden');
            elScanResults.classList.add('hidden');
            loadJobs();
            loadUser(); // Refresh XP
        } else {
            const errData = await response.json();
            alert('Failed to save: ' + (errData.message || 'Unknown error'));
        }
    } catch (err) {
        alert('Connection error');
    } finally {
        elBtnSaveJob.disabled = false;
        elBtnSaveJob.innerText = 'Save to GrowthZone';
    }
});
