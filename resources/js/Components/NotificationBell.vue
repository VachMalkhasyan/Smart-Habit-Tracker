<template>
    <div class="relative" ref="bellRef">
        <!-- Bell Button -->
        <button
            @click="togglePanel"
            class="relative p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none"
            aria-label="Notifications"
        >
            <Bell :class="['w-5 h-5 transition-colors', bellRinging ? 'bell-ring text-indigo-600' : '']" />
            <!-- Unread badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold px-1 leading-none"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Panel -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-1 scale-95"
        >
            <div
                v-if="panelOpen"
                class="absolute right-0 top-full mt-2 w-[380px] bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl z-50 overflow-hidden flex flex-col"
                style="max-height: 480px;"
            >
                <!-- Panel Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Notifications</h3>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium transition-colors"
                    >
                        Mark all read
                    </button>
                </div>

                <!-- Notification List -->
                <div class="overflow-y-auto flex-1" @scroll.passive="() => {}">
                    <!-- Loading -->
                    <div v-if="loading" class="flex items-center justify-center py-10">
                        <div class="w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin" />
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="notifications.length === 0" class="flex flex-col items-center justify-center py-10 px-4 text-center">
                        <!-- SVG Bell with Zzz -->
                        <svg width="56" height="56" viewBox="0 0 56 56" fill="none" class="mb-3 opacity-40">
                            <path d="M28 6C18.06 6 10 14.06 10 24v10l-3 4v2h42v-2l-3-4V24c0-9.94-8.06-18-18-18z" fill="#6366f1" opacity="0.3"/>
                            <path d="M28 6C18.06 6 10 14.06 10 24v10l-3 4v2h42v-2l-3-4V24c0-9.94-8.06-18-18-18z" stroke="#6366f1" stroke-width="2" fill="none"/>
                            <circle cx="28" cy="44" r="3" fill="#6366f1" opacity="0.5"/>
                            <text x="36" y="18" font-size="10" fill="#6366f1" opacity="0.7" font-family="sans-serif">z</text>
                            <text x="41" y="12" font-size="8" fill="#6366f1" opacity="0.5" font-family="sans-serif">z</text>
                            <text x="45" y="8" font-size="6" fill="#6366f1" opacity="0.3" font-family="sans-serif">z</text>
                        </svg>
                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">You're all caught up! 🎉</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">No new notifications</p>
                    </div>

                    <!-- Notification Items -->
                    <TransitionGroup
                        v-else
                        name="notif"
                        tag="div"
                    >
                        <div
                            v-for="notif in notifications"
                            :key="notif.id"
                            @click="openNotification(notif)"
                            :class="[
                                'flex items-start gap-3 px-4 py-3 cursor-pointer transition-colors relative group',
                                'hover:bg-gray-50 dark:hover:bg-gray-800/60',
                                !notif.read_at
                                    ? 'border-l-2 border-indigo-500 bg-indigo-50/40 dark:bg-indigo-900/10'
                                    : 'border-l-2 border-transparent'
                            ]"
                        >
                            <!-- Icon Circle -->
                            <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-lg shrink-0', typeColors[notif.type] ?? 'bg-gray-100']">
                                {{ notif.icon }}
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-semibold text-gray-900 dark:text-gray-100 leading-tight">
                                    {{ notif.title }}
                                    <span v-if="!notif.read_at" class="inline-block w-2 h-2 bg-indigo-500 rounded-full ml-1 align-middle" />
                                </p>
                                <p class="text-[12px] text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">
                                    {{ notif.message }}
                                </p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                                    {{ formatTime(notif.created_at) }}
                                </p>
                            </div>

                            <!-- Delete on hover -->
                            <button
                                @click.stop="deleteNotification(notif.id)"
                                class="opacity-0 group-hover:opacity-100 transition-opacity ml-1 p-1 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 shrink-0"
                                title="Delete"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </TransitionGroup>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { Bell, Trash2 } from 'lucide-vue-next'
import axios from 'axios'

const props = defineProps({
    unreadCount: {
        type: Number,
        default: 0,
    },
})

const emit = defineEmits(['update:unreadCount', 'notificationReceived'])

// State
const panelOpen     = ref(false)
const loading       = ref(false)
const notifications = ref([])
const bellRinging   = ref(false)
const bellRef       = ref(null)

// Expose a method for parent to prepend a new notification
function prependNotification(notif) {
    notifications.value.unshift(notif)
    triggerBellRing()
}
defineExpose({ prependNotification })

// Color coding per notification type
const typeColors = {
    friend_request: 'bg-blue-100 dark:bg-blue-900/30',
    friend_cheered: 'bg-pink-100 dark:bg-pink-900/30',
    streak_at_risk: 'bg-orange-100 dark:bg-orange-900/30',
    daily_missed:   'bg-green-100 dark:bg-green-900/30',
    leveled_up:     'bg-purple-100 dark:bg-purple-900/30',
    all_habits_done:'bg-indigo-100 dark:bg-indigo-900/30',
}

function togglePanel() {
    panelOpen.value = !panelOpen.value
    if (panelOpen.value && notifications.value.length === 0) {
        fetchNotifications()
    }
}

async function fetchNotifications() {
    loading.value = true
    try {
        const { data } = await axios.get('/notifications')
        notifications.value = data.notifications
    } catch (e) {
        console.error('[NotificationBell] Failed to fetch notifications', e)
    } finally {
        loading.value = false
    }
}

async function markAllRead() {
    try {
        await axios.post('/notifications/read-all')
        notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date().toISOString() }))
        emit('update:unreadCount', 0)
    } catch (e) {
        console.error('[NotificationBell] Failed to mark all read', e)
    }
}

async function openNotification(notif) {
    if (!notif.read_at) {
        try {
            const { data } = await axios.post(`/notifications/${notif.id}/read`)
            notifications.value = notifications.value.map(n =>
                n.id === notif.id ? { ...n, read_at: new Date().toISOString() } : n
            )
            emit('update:unreadCount', data.unread_count)
        } catch (e) {
            console.error('[NotificationBell] Failed to mark read', e)
        }
    }
    panelOpen.value = false
    router.visit(notif.url)
}

async function deleteNotification(id) {
    try {
        const { data } = await axios.delete(`/notifications/${id}`)
        notifications.value = notifications.value.filter(n => n.id !== id)
        emit('update:unreadCount', data.unread_count)
    } catch (e) {
        console.error('[NotificationBell] Failed to delete notification', e)
    }
}

function triggerBellRing() {
    bellRinging.value = false
    requestAnimationFrame(() => {
        bellRinging.value = true
        setTimeout(() => { bellRinging.value = false }, 600)
    })
}

// Relative time formatting
function formatTime(dateStr) {
    if (!dateStr) return ''
    const diff = Date.now() - new Date(dateStr).getTime()
    const mins  = Math.floor(diff / 60000)
    const hours = Math.floor(diff / 3600000)
    const days  = Math.floor(diff / 86400000)
    if (mins < 1)    return 'Just now'
    if (mins < 60)   return `${mins} min ago`
    if (hours < 24)  return `${hours} hour${hours > 1 ? 's' : ''} ago`
    return `${days} day${days > 1 ? 's' : ''} ago`
}

// Close on outside click
function onOutsideClick(e) {
    if (bellRef.value && !bellRef.value.contains(e.target)) {
        panelOpen.value = false
    }
}

onMounted(() => document.addEventListener('mousedown', onOutsideClick))
onUnmounted(() => document.removeEventListener('mousedown', onOutsideClick))
</script>

<style scoped>
@keyframes bell-ring {
    0%, 100% { transform: rotate(0deg); }
    20%       { transform: rotate(15deg); }
    40%       { transform: rotate(-15deg); }
    60%       { transform: rotate(10deg); }
    80%       { transform: rotate(-10deg); }
}
.bell-ring {
    animation: bell-ring 0.5s ease;
    transform-origin: top center;
}

/* Slide-down animation for new notifications */
.notif-enter-active {
    transition: all 0.3s ease;
}
.notif-enter-from {
    opacity: 0;
    transform: translateY(-12px);
}
.notif-enter-to {
    opacity: 1;
    transform: translateY(0);
}
.notif-leave-active {
    transition: all 0.2s ease;
}
.notif-leave-from {
    opacity: 1;
    transform: translateY(0);
}
.notif-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

/* Clamp message text to 2 lines */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
