import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useChartTheme() {
    const page = usePage()

    const isDark = computed(() => {
        const theme = page.props.auth?.user?.settings?.theme ?? 'system'
        if (theme === 'dark') return true
        if (theme === 'light') return false
        if (typeof window !== 'undefined') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches
        }
        return false
    })

    const chartTheme = computed(() => ({
        theme: {
            mode: isDark.value ? 'dark' : 'light',
        },
        chart: {
            background: 'transparent',
            foreColor:  isDark.value ? '#e5e7eb' : '#374151',
        },
        tooltip: {
            theme: isDark.value ? 'dark' : 'light',
        },
        grid: {
            borderColor: isDark.value ? '#374151' : '#e5e7eb',
        },
    }))

    return { chartTheme, isDark }
}
