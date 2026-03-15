<template>
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5 h-full flex flex-col">
        <h2 class="font-semibold text-gray-800 dark:text-gray-200 mb-4 shrink-0">Weekly Progress</h2>
        <div class="flex-1 min-h-[180px]">
            <apexchart
                type="bar"
                height="100%"
                :options="chartOptions"
                :series="chartSeries"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useChartTheme } from '@/composables/useChartTheme'

const props = defineProps({
    weeklyData: { type: Array, default: () => [] }
})

const { chartTheme } = useChartTheme()

const chartOptions = computed(() => ({
    ...chartTheme.value,
    chart: { ...chartTheme.value.chart, toolbar: { show: false }, sparkline: { enabled: false } },
    plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.weeklyData?.map(d => d.day) ?? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        labels: { style: { fontSize: '11px', colors: '#9ca3af' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { style: { colors: '#9ca3af' }, formatter: (v) => Math.round(v) } },
    colors: ['#6366f1'],
}))

const chartSeries = computed(() => [{
    name: 'Completed',
    data: props.weeklyData?.map(d => d.completed) ?? [0,0,0,0,0,0,0],
}])
</script>
