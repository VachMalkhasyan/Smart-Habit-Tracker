import { onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

export function useShortcuts(extras = {}) {
    const page = usePage()

    const getShortcuts = () => {
        return page.props.auth?.user?.settings?.shortcuts ?? {
            dashboard:   'd',
            habits:      'h',
            create:      'c',
            categories:  'k',
            settings:    's',
            search:      '/',
            help:        '?',
            analytics:   'n',
            friends:     'f',
            templates:   't',
            onboarding:  'o',
            ai:          'i',
            ai_assistant: 'a',
        }
    }

    const handler = (e) => {
        const tag = document.activeElement?.tagName?.toLowerCase()
        if (['input', 'textarea', 'select'].includes(tag)) return
        if (document.activeElement?.isContentEditable) return

        const key = e.key.toLowerCase()
        const shortcuts = getShortcuts()

        if (e.key === 'Escape' && extras.onEscape) {
            extras.onEscape()
            return
        }

        if (key === shortcuts.help && extras.onHelp) {
            extras.onHelp()
            return
        }

        if (key === shortcuts.ai_assistant) {
            if (extras.onAiAssistant) {
                extras.onAiAssistant()
            } else {
                window.dispatchEvent(new CustomEvent('toggle-ai-widget'))
            }
            return
        }

        if (key === shortcuts.search) {
            e.preventDefault()
            if (extras.onSearch) {
                extras.onSearch()
                return
            }
            document.querySelector('input[placeholder*="Search"]')?.focus()
            return
        }

        const navigationMap = {
            [shortcuts.dashboard]:  () => router.visit(route('dashboard')),
            [shortcuts.habits]:     () => router.visit(route('habits.index')),
            [shortcuts.create]:     () => router.visit(route('habits.create')),
            [shortcuts.categories]: () => router.visit(route('categories.index')),
            [shortcuts.settings]:   () => router.visit(route('settings')),
            [shortcuts.analytics]:  () => router.visit(route('analytics')),
            [shortcuts.friends]:    () => router.visit(route('friends.index')),
            [shortcuts.templates]:  () => router.visit(route('templates.index')),
            [shortcuts.onboarding]: () => router.visit(route('onboarding')),
            [shortcuts.ai]:         () => router.visit(route('ai.index')),
        }

        if (navigationMap[key]) {
            navigationMap[key]()
        }
    }

    onMounted(() => window.addEventListener('keydown', handler))
    onUnmounted(() => window.removeEventListener('keydown', handler))
}
