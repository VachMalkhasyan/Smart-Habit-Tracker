<template>
    <AppLayout title="Settings" subtitle="Manage your account preferences">

        <div class="max-w-2xl mx-auto space-y-6">

            <!-- Notifications -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-5">
                    <Bell class="w-5 h-5 text-indigo-500" />
                    <h2 class="font-semibold text-gray-800">Notifications</h2>
                </div>

                <div class="space-y-4">
                    <div v-for="setting in notificationSettings" :key="setting.key"
                         class="flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ setting.label }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ setting.description }}</p>
                        </div>
                        <button @click="toggle(setting.key)"
                                :class="[
                                'relative w-11 h-6 rounded-full transition-colors duration-200',
                                form[setting.key] ? 'bg-indigo-600' : 'bg-gray-200'
                            ]">
                            <span :class="[
                                'absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200',
                                form[setting.key] ? 'translate-x-5' : 'translate-x-0'
                            ]"></span>
                        </button>
                    </div>
                    <div v-if="form.email_reminders" class="mt-4 space-y-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Daily Reminder Time</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">When to send your morning reminder</p>
                            </div>
                            <input v-model="form.reminder_time" type="time"
                                   class="text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600" />
                        </div>

                        <div v-if="form.weekly_summary" class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Weekly Summary Day</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Which day to receive your weekly report</p>
                            </div>
                            <select v-model="form.weekly_summary_day"
                                    class="text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600">
                                <option value="monday">Monday</option>
                                <option value="tuesday">Tuesday</option>
                                <option value="wednesday">Wednesday</option>
                                <option value="thursday">Thursday</option>
                                <option value="friday">Friday</option>
                                <option value="saturday">Saturday</option>
                                <option value="sunday">Sunday</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appearance -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-5">
                    <Palette class="w-5 h-5 text-indigo-500" />
                    <h2 class="font-semibold text-gray-800">Appearance</h2>
                </div>
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 block">
                        Public Profile
                    </label>

                    <div class="space-y-3">
                        <!-- Username -->
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Username</label>
                            <input v-model="form.username" type="text"
                                   placeholder="e.g. john_doe"
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600" />
                        </div>

                        <!-- Bio -->
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Bio</label>
                            <input v-model="form.bio" type="text"
                                   placeholder="A short bio..."
                                   class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600" />
                        </div>

                        <!-- Public toggle -->
                        <div class="flex items-center justify-between py-2">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Public Profile</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Anyone can view your progress</p>
                            </div>
                            <button @click="form.is_public = !form.is_public"
                                    :class="['relative w-11 h-6 rounded-full transition-colors duration-200',
                    form.is_public ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700']">
                <span :class="['absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200',
                    form.is_public ? 'translate-x-5' : 'translate-x-0']">
                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Theme -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 block">Theme</label>
                        <div class="flex gap-3">
                            <button v-for="theme in themes" :key="theme.value"
                                    @click="form.theme = theme.value"
                                    :class="[
                                    'flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl border text-sm font-medium transition-all',
                                    form.theme === theme.value
                                        ? 'border-indigo-400 bg-indigo-50 text-indigo-600'
                                        : 'border-gray-200 text-gray-500 hover:border-gray-300'
                                ]">
                                <component :is="theme.icon" class="w-4 h-4" />
                                {{ theme.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Week starts on -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 block">Week starts on</label>
                        <div class="flex gap-2">
                            <button v-for="day in weekDays" :key="day.value"
                                    @click="form.week_start = day.value"
                                    :class="[
                                    'flex-1 py-2 rounded-xl border text-sm font-medium transition-all',
                                    form.week_start === day.value
                                        ? 'border-indigo-400 bg-indigo-50 text-indigo-600'
                                        : 'border-gray-200 text-gray-500 hover:border-gray-300'
                                ]">
                                {{ day.label }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Habit Defaults -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-5">
                    <SlidersHorizontal class="w-5 h-5 text-indigo-500" />
                    <h2 class="font-semibold text-gray-800">Habit Defaults</h2>
                </div>

                <div class="space-y-4">
                    <!-- Default priority -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 block">Default Priority</label>
                        <div class="flex gap-3">
                            <button v-for="p in priorities" :key="p.value"
                                    @click="form.default_priority = p.value"
                                    :class="[
                                    'flex-1 py-2.5 rounded-xl border text-sm font-medium transition-all',
                                    form.default_priority === p.value ? p.activeClass : 'border-gray-200 text-gray-500 hover:border-gray-300'
                                ]">
                                {{ p.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Default goal unit -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 block">Default Goal Unit</label>
                        <select v-model="form.default_goal_unit"
                                class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600">
                            <option value="days">Days</option>
                            <option value="weeks">Weeks</option>
                            <option value="months">Months</option>
                            <option value="years">Years</option>
                        </select>
                    </div>
                </div>
            </div>
            <!-- Keyboard Shortcuts -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-5">
                    <Keyboard class="w-5 h-5 text-indigo-500" />
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-0">Keyboard Shortcuts</h2>
                </div>

                <div class="space-y-3">
                    <div v-for="shortcut in shortcutList" :key="shortcut.key"
                         class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-800 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ shortcut.label }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ shortcut.description }}</p>
                        </div>
                        <input
                            :value="form.shortcuts[shortcut.key]"
                            @keydown.prevent="captureKey($event, shortcut.key)"
                            type="text"
                            maxlength="1"
                            placeholder="—"
                            readonly
                            class="w-12 h-9 text-center text-sm font-bold border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600 cursor-pointer uppercase"
                        />
                    </div>
                </div>

                <button @click="resetShortcuts"
                        class="mt-4 text-xs text-gray-400 dark:text-gray-500 hover:text-indigo-500 transition-colors">
                    Reset to defaults
                </button>
            </div>

            <!-- Export Data -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-5">
                    <Download class="w-5 h-5 text-indigo-500" />
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Export Data</h2>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Download your habits and history as CSV or PDF
                </p>
                <ExportButton />
            </div>
            <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Setup Wizard</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Re-run the onboarding wizard</p>
                    </div>
                    <Link :href="route('onboarding.reset')" method="post" as="button">
                        <Button variant="outline" size="sm"
                                class="gap-2 dark:border-gray-700 dark:text-gray-300">
                            <Wand2 class="w-4 h-4" />
                            Restart Wizard
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-red-100 dark:border-red-900 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-5">
                    <ShieldAlert class="w-5 h-5 text-red-500" />
                    <h2 class="font-semibold text-red-600">Danger Zone</h2>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Delete all habits</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Permanently delete all your habits and history</p>
                        </div>
                        <Button @click="dangerDialog = 'habits'"
                                variant="outline"
                                class="text-red-500 border-red-200 hover:bg-red-50 shrink-0">
                            Delete All
                        </Button>
                    </div>
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-3 flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Delete account</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Permanently delete your account and all data</p>
                        </div>
                        <Button @click="dangerDialog = 'account'"
                                variant="outline"
                                class="text-red-500 border-red-200 hover:bg-red-50 shrink-0">
                            Delete Account
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end pb-6">
                <Button @click="save"
                        :disabled="form.processing"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 px-8">
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <Check v-else class="w-4 h-4" />
                    Save Settings
                </Button>
            </div>
        </div>

        <!-- Danger Dialog -->
        <Dialog v-model:open="dangerDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ dangerDialog === 'habits' ? 'Delete All Habits' : 'Delete Account' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ dangerDialog === 'habits'
                        ? 'This will permanently delete all your habits and completion history. This cannot be undone.'
                        : 'This will permanently delete your account and all associated data. This cannot be undone.'
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="dangerDialog = null">Cancel</Button>
                    <Button variant="destructive" @click="executeDanger">
                        Yes, Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <!-- Dirty Banner (fixed bottom) -->
        <Transition name="banner-slide">
            <div v-if="isDirty"
                class="fixed bottom-0 left-0 right-0 z-50 flex items-center justify-between gap-4 px-6 py-4 bg-white dark:bg-gray-900 border-t border-indigo-200 dark:border-indigo-800 shadow-lg"
            >
                <p class="text-sm text-gray-600 dark:text-gray-300 font-medium flex items-center gap-2">
                    <span class="w-2 h-2 bg-amber-400 rounded-full animate-pulse"></span>
                    You have unsaved changes
                </p>
                <div class="flex items-center gap-3">
                    <button @click="discardChanges"
                        class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                    >Discard</button>
                    <Button @click="save"
                        :disabled="form.processing"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 text-sm px-5"
                    >
                        <Loader2 v-if="form.processing" class="w-3.5 h-3.5 animate-spin" />
                        <Check v-else class="w-3.5 h-3.5" />
                        Save Now
                    </Button>
                </div>
            </div>
        </Transition>

        <!-- Unsaved Changes Modal -->
        <UnsavedChangesModal :show="showModal" @confirm="confirmLeave" @cancel="cancelLeave" />

    </AppLayout>
</template>

<script setup>
import {ref, computed, watch} from 'vue'
import {useForm, router, usePage, Link} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription, DialogFooter
} from '@/components/ui/dialog'
import {
    Bell, Palette, SlidersHorizontal, ShieldAlert,
    Sun, Moon, Monitor, Check, Loader2, Keyboard, Download, Wand2
} from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme'
import {toast} from "vue-sonner";
import ExportButton from "@/Components/ExportButton.vue";
import { useUnsavedChanges } from '@/composables/useUnsavedChanges'
import UnsavedChangesModal from '@/Components/UnsavedChangesModal.vue'


const props = defineProps({
    settings: Object,
})

const defaultShortcuts = {
    dashboard:   'd',
    habits:      'h',
    create:      'c',
    categories:  'k',
    settings:    's',
    search:      '/',
    help:        '?',
    analytics:   'a',
    friends:     'f',
    templates:   't',
}

const shortcutList = [
    { key: 'dashboard',  label: 'Dashboard',      description: 'Go to dashboard' },
    { key: 'habits',     label: 'My Habits',       description: 'Go to habits list' },
    { key: 'create',     label: 'Create Habit',    description: 'Open create habit form' },
    { key: 'analytics',  label: 'Analytics',       description: 'Go to analytics page' },
    { key: 'friends',    label: 'Friends',         description: 'Go to friends page' },
    { key: 'templates',  label: 'Templates',       description: 'Go to templates page' },
    { key: 'categories', label: 'Categories',      description: 'Go to categories' },
    { key: 'settings',   label: 'Settings',        description: 'Go to settings' },
    { key: 'search',     label: 'Focus Search',    description: 'Open global search' },
    { key: 'help',       label: 'Show Shortcuts',  description: 'Show keyboard shortcuts help' },
]
const captureKey = (event, action) => {
    const key = event.key.toLowerCase()

    // Block special keys
    if (['shift', 'control', 'alt', 'meta', 'tab', 'enter'].includes(key)) return

    // Check for duplicate
    const duplicate = Object.entries(form.shortcuts).find(
        ([k, v]) => v === key && k !== action
    )

    if (duplicate) {
        toast.warning(`"${key.toUpperCase()}" is already used for ${duplicate[0]}`)
        return
    }

    form.shortcuts[action] = key
}

const resetShortcuts = () => {
    form.shortcuts = { ...defaultShortcuts }
    toast.info('Shortcuts reset to defaults')
}

const form = useForm({
    email_reminders:      props.settings?.email_reminders      ?? true,
    missed_habit_alerts:  props.settings?.missed_habit_alerts  ?? true,
    weekly_summary:       props.settings?.weekly_summary       ?? false,
    reminder_time:        props.settings?.reminder_time        ?? '08:00',
    weekly_summary_day:   props.settings?.weekly_summary_day   ?? 'monday',
    theme:                props.settings?.theme                ?? 'system',
    week_start:           props.settings?.week_start           ?? 'monday',
    default_priority:     props.settings?.default_priority     ?? 2,
    default_goal_unit:    props.settings?.default_goal_unit    ?? 'days',
    shortcuts:            props.settings?.shortcuts            ?? defaultShortcuts,
    username:             props.settings?.username   ?? '',
    bio:                  props.settings?.bio        ?? '',
    is_public:            props.settings?.is_public  ?? false,

})

// Notification toggles
const notificationSettings = [
    { key: 'email_reminders',     label: 'Email Reminders',     description: 'Get reminded when you have habits due' },
    { key: 'missed_habit_alerts', label: 'Missed Habit Alerts',  description: 'Get notified when you miss a habit' },
    { key: 'weekly_summary',      label: 'Weekly Summary',       description: 'Receive a weekly progress report' },
]


const page = usePage()  // ← add this
const { applyTheme } = useTheme()

const toggle = (key) => form[key] = !form[key]

// Apply theme instantly when toggled
watch(() => form.theme, (newTheme) => {
    applyTheme(newTheme)
})

// Re-apply correct theme after save completes
const removeListener = router.on('finish', () => {
    const theme = page.props.auth?.user?.settings?.theme ?? 'system'
    applyTheme(theme)
})

// Theme options
const themes = [
    { value: 'light',  label: 'Light',  icon: Sun },
    { value: 'dark',   label: 'Dark',   icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
]

// Week days
const weekDays = [
    { value: 'monday',  label: 'Monday' },
    { value: 'sunday',  label: 'Sunday' },
    { value: 'saturday', label: 'Saturday' },
]

// Priorities
const priorities = [
    { value: 1, label: '🔴 High',   activeClass: 'border-red-400 bg-red-50 text-red-600' },
    { value: 2, label: '🟡 Medium', activeClass: 'border-yellow-400 bg-yellow-50 text-yellow-600' },
    { value: 3, label: '🟢 Low',    activeClass: 'border-green-400 bg-green-50 text-green-600' },
]


// Snapshot the initial form values for dirty comparison
const originalForm = JSON.stringify({
    email_reminders:     props.settings?.email_reminders     ?? true,
    missed_habit_alerts: props.settings?.missed_habit_alerts ?? true,
    weekly_summary:      props.settings?.weekly_summary      ?? false,
    reminder_time:       props.settings?.reminder_time       ?? '08:00',
    weekly_summary_day:  props.settings?.weekly_summary_day  ?? 'monday',
    theme:               props.settings?.theme               ?? 'system',
    week_start:          props.settings?.week_start          ?? 'monday',
    default_priority:    props.settings?.default_priority    ?? 2,
    default_goal_unit:   props.settings?.default_goal_unit   ?? 'days',
    shortcuts:           props.settings?.shortcuts           ?? defaultShortcuts,
    username:            props.settings?.username  ?? '',
    bio:                 props.settings?.bio       ?? '',
    is_public:           props.settings?.is_public ?? false,
})

const isDirty = computed(() => JSON.stringify({
    email_reminders:     form.email_reminders,
    missed_habit_alerts: form.missed_habit_alerts,
    weekly_summary:      form.weekly_summary,
    reminder_time:       form.reminder_time,
    weekly_summary_day:  form.weekly_summary_day,
    theme:               form.theme,
    week_start:          form.week_start,
    default_priority:    form.default_priority,
    default_goal_unit:   form.default_goal_unit,
    shortcuts:           form.shortcuts,
    username:            form.username,
    bio:                 form.bio,
    is_public:           form.is_public,
}) !== originalForm)

const { showModal, confirmLeave, cancelLeave, setBypass } = useUnsavedChanges(isDirty)

const discardChanges = () => {
    const parsed = JSON.parse(originalForm)
    Object.keys(parsed).forEach(k => form[k] = parsed[k])
}

// Save
const save = () => {
    setBypass(true)
    form.post(route('settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            const theme = page.props.auth?.user?.settings?.theme ?? 'system'
            applyTheme(theme)
        },
        onError: () => setBypass(false)
    })
}


// Danger zone
const dangerDialog = ref(null)
const dangerDialogOpen = computed({
    get: () => dangerDialog.value !== null,
    set: (v) => { if (!v) dangerDialog.value = null }
})

const executeDanger = () => {
    if (dangerDialog.value === 'habits') {
        router.delete(route('settings.deleteHabits'), {
            onSuccess: () => { dangerDialog.value = null }
        })
    } else {
        router.delete(route('settings.deleteAccount'), {
            onSuccess: () => { dangerDialog.value = null }
        })
    }
}

</script>

<style scoped>
.banner-slide-enter-active, .banner-slide-leave-active { transition: transform 0.3s ease, opacity 0.3s ease; }
.banner-slide-enter-from, .banner-slide-leave-to       { transform: translateY(100%); opacity: 0; }
</style>
