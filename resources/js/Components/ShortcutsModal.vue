<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-w-md dark:bg-gray-900">
            <DialogHeader>
                <DialogTitle class="dark:text-gray-100">⌨️ Keyboard Shortcuts</DialogTitle>
                <DialogDescription class="dark:text-gray-400">
                    Your current keyboard shortcuts
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-2 py-2">
                <div v-for="shortcut in shortcutList" :key="shortcut.key"
                     class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-800 last:border-0">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ shortcut.label }}</span>
                    <kbd class="px-2.5 py-1 text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-lg uppercase">
                        {{ shortcuts[shortcut.key] ?? '—' }}
                    </kbd>
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
        }
)

const shortcutList = [
    { key: 'dashboard',  label: 'Go to Dashboard' },
    { key: 'habits',     label: 'Go to My Habits' },
    { key: 'create',     label: 'Create New Habit' },
    { key: 'analytics',  label: 'Go to Analytics' },
    { key: 'friends',    label: 'Go to Friends' },
    { key: 'templates',  label: 'Go to Templates' },
    { key: 'categories', label: 'Go to Categories' },
    { key: 'settings',   label: 'Go to Settings' },
    { key: 'search',     label: 'Open Search' },
    { key: 'help',       label: 'Show This Modal' },
    { key: 'onboarding', label: 'Restart Onboarding Wizard' },

]
</script>
