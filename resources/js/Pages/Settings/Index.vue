<template>
    <AppLayout title="Settings" subtitle="Manage your account preferences">

        <div class="max-w-2xl mx-auto space-y-6">

            <!-- Notifications -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-5">
                    <Bell class="w-5 h-5 text-indigo-500" />
                    <h2 class="font-semibold text-gray-800">Notifications</h2>
                </div>

                <div class="space-y-4">
                    <div v-for="setting in notificationSettings" :key="setting.key"
                         class="flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ setting.label }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ setting.description }}</p>
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
                </div>
            </div>

            <!-- Appearance -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-5">
                    <Palette class="w-5 h-5 text-indigo-500" />
                    <h2 class="font-semibold text-gray-800">Appearance</h2>
                </div>

                <div class="space-y-4">
                    <!-- Theme -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-2 block">Theme</label>
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
                        <label class="text-sm font-medium text-gray-700 mb-2 block">Week starts on</label>
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
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-5">
                    <SlidersHorizontal class="w-5 h-5 text-indigo-500" />
                    <h2 class="font-semibold text-gray-800">Habit Defaults</h2>
                </div>

                <div class="space-y-4">
                    <!-- Default priority -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-2 block">Default Priority</label>
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
                        <label class="text-sm font-medium text-gray-700 mb-2 block">Default Goal Unit</label>
                        <select v-model="form.default_goal_unit"
                                class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="days">Days</option>
                            <option value="weeks">Weeks</option>
                            <option value="months">Months</option>
                            <option value="years">Years</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-5">
                    <ShieldAlert class="w-5 h-5 text-red-500" />
                    <h2 class="font-semibold text-red-600">Danger Zone</h2>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Delete all habits</p>
                            <p class="text-xs text-gray-400 mt-0.5">Permanently delete all your habits and history</p>
                        </div>
                        <Button @click="dangerDialog = 'habits'"
                                variant="outline"
                                class="text-red-500 border-red-200 hover:bg-red-50 shrink-0">
                            Delete All
                        </Button>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Delete account</p>
                            <p class="text-xs text-gray-400 mt-0.5">Permanently delete your account and all data</p>
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

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription, DialogFooter
} from '@/components/ui/dialog'
import {
    Bell, Palette, SlidersHorizontal, ShieldAlert,
    Sun, Moon, Monitor, Check, Loader2
} from 'lucide-vue-next'

const props = defineProps({
    settings: Object,
})

const form = useForm({
    email_reminders:      props.settings?.email_reminders      ?? true,
    missed_habit_alerts:  props.settings?.missed_habit_alerts  ?? true,
    weekly_summary:       props.settings?.weekly_summary       ?? false,
    theme:                props.settings?.theme                ?? 'system',
    week_start:           props.settings?.week_start           ?? 'monday',
    default_priority:     props.settings?.default_priority     ?? 2,
    default_goal_unit:    props.settings?.default_goal_unit    ?? 'days',
})

// Notification toggles
const notificationSettings = [
    { key: 'email_reminders',     label: 'Email Reminders',     description: 'Get reminded when you have habits due' },
    { key: 'missed_habit_alerts', label: 'Missed Habit Alerts',  description: 'Get notified when you miss a habit' },
    { key: 'weekly_summary',      label: 'Weekly Summary',       description: 'Receive a weekly progress report' },
]

const toggle = (key) => form[key] = !form[key]

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

// Save
const save = () => {
    form.post(route('settings.update'))
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
