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
import { useChartTheme } from '@/composables/useChartTheme'

const props = defineProps({
    monthlyTrend: { type: Array, default: () => [] }
})

const { chartTheme } = useChartTheme()

const monthlyChartOptions = computed(() => ({
    ...chartTheme.value,
    chart: { ...chartTheme.value.chart, toolbar: { show: false } },
    stroke:      { curve: 'smooth', width: 2 },
    fill:        { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    dataLabels:  { enabled: false },
    xaxis: {
        categories: props.monthlyTrend?.map(d => d.month) ?? [],
        labels:     { style: { colors: '#9ca3af' } },
        axisBorder: { show: false },
        axisTicks:  { show: false },
    },
    yaxis:  { labels: { style: { colors: '#9ca3af' }, formatter: (v) => Math.round(v) } },
    colors: ['#6366f1'],
}))

const monthlyChartSeries = computed(() => [{
    name: 'Completions',
    data: props.monthlyTrend?.map(d => d.total ?? d.completed) ?? [],
}])
</script>
