<template>
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 h-full flex flex-col">
        <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-1 shrink-0">Monthly Trend</h2>
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-4 shrink-0">Completions over last 6 months</p>
        <div class="flex-1 min-h-[200px]">
            <apexchart
                type="area"
                height="100%"
                :options="monthlyChartOptions"
                :series="monthlyChartSeries"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    monthlyTrend: { type: Array, default: () => [] }
})

const isDark = computed(() =>
    document.documentElement.classList.contains('dark')
)

const monthlyChartOptions = computed(() => ({
    chart:       { toolbar: { show: false } },
    stroke:      { curve: 'smooth', width: 2 },
    fill:        { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    dataLabels:  { enabled: false },
    theme:       { mode: isDark.value ? 'dark' : 'light' },
    xaxis: {
        categories: props.monthlyTrend?.map(d => d.month) ?? [],
        labels:     { style: { colors: '#9ca3af' } },
        axisBorder: { show: false },
        axisTicks:  { show: false },
    },
    yaxis:  { labels: { style: { colors: '#9ca3af' }, formatter: (v) => Math.round(v) } },
    grid:   { borderColor: isDark.value ? '#374151' : '#f3f4f6', strokeDashArray: 4 },
    colors: ['#6366f1'],
    tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

const monthlyChartSeries = computed(() => [{
    name: 'Completions',
    data: props.monthlyTrend?.map(d => d.total ?? d.completed) ?? [],
}])
</script>
