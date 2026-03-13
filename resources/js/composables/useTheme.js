import { watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useTheme() {
    const page = usePage()

    const applyTheme = (theme) => {
        const root = document.documentElement

        if (theme === 'dark') {
            root.classList.add('dark')
        } else if (theme === 'light') {
            root.classList.remove('dark')
        } else {
            // system
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
            prefersDark ? root.classList.add('dark') : root.classList.remove('dark')
        }
    }

    // Apply on mount
    onMounted(() => {
        const theme = page.props.auth?.user?.settings?.theme ?? 'system'
        applyTheme(theme)

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            const current = page.props.auth?.user?.settings?.theme ?? 'system'
            if (current === 'system') {
                e.matches ? document.documentElement.classList.add('dark')
                    : document.documentElement.classList.remove('dark')
            }
        })
    })

    // Re-apply when settings change
    watch(
        () => page.props.auth?.user?.settings?.theme,
        (theme) => { if (theme) applyTheme(theme) }
    )

    return { applyTheme }
}
