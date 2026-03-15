<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-w-md dark:bg-gray-900">
            <DialogHeader>
                <DialogTitle class="dark:text-gray-100">⌨️ Keyboard Shortcuts</DialogTitle>
                <DialogDescription class="dark:text-gray-400">
                    Your current keyboard shortcuts
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-2 max-h-[60vh] overflow-y-auto px-1 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700">
                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Navigation</h3>
                    <div class="space-y-1">
                        <div v-for="shortcut in navigationShortcuts" :key="shortcut.key"
                             class="flex items-center justify-between py-1.5 border-b border-gray-50 dark:border-gray-800 last:border-0">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ shortcut.label }}</span>
                            <kbd class="px-2.5 py-1 text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-lg uppercase">
                                {{ shortcuts[shortcut.key] ?? '—' }}
                            </kbd>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2 mt-4">Actions</h3>
                    <div class="space-y-1">
                        <div v-for="shortcut in actionShortcuts" :key="shortcut.key"
                             class="flex items-center justify-between py-1.5 border-b border-gray-50 dark:border-gray-800 last:border-0">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ shortcut.label }}</span>
                            <kbd class="px-2.5 py-1 text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-lg uppercase">
                                {{ shortcuts[shortcut.key] ?? '—' }}
                            </kbd>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-xs text-gray-400 dark:text-gray-500 text-center mt-2">
                Customize shortcuts in Settings
            </p>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription
} from '@/components/ui/dialog'

const props = defineProps({
    open: Boolean
})

const emit = defineEmits(['update:open'])

const page = usePage()

const shortcuts = computed(() =>
    page.props.auth?.user?.settings?.shortcuts ?? {
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
        onboarding:  'o',
        pomodoro:    'p',
        mood:        'm',
        ai_coach:    'i',
        ai_widget:   'q',
        focus_mode:  'z',
        diary:       'j',
    }
)

const navigationShortcuts = [
    { key: 'dashboard',  label: 'Go to Dashboard' },
    { key: 'habits',     label: 'Go to My Habits' },
    { key: 'create',     label: 'Create Habit' },
    { key: 'analytics',  label: 'Go to Analytics' },
    { key: 'friends',    label: 'Go to Friends' },
    { key: 'templates',  label: 'Go to Templates' },
    { key: 'categories', label: 'Go to Categories' },
    { key: 'pomodoro',   label: 'Open Pomodoro' },
    { key: 'mood',       label: 'Mood Check-in' },
    { key: 'ai_coach',   label: 'Open AI Coach' },
    { key: 'settings',   label: 'Go to Settings' },
]

const actionShortcuts = [
    { key: 'search',     label: 'Open Search' },
    { key: 'help',       label: 'Show Shortcuts Modal' },
    { key: 'onboarding', label: 'Restart Setup' },
    { key: 'ai_widget',  label: 'Toggle AI Widget' },
    { key: 'focus_mode', label: 'Focus Mode (Soon)' },
    { key: 'diary',      label: 'Journal (Soon)' },
]
</script>
