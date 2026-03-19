<script setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import MoodCheckIn from '@/Components/MoodCheckIn.vue'
import AffirmationCard from '@/Components/AffirmationCard.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { TrendingUp, TrendingDown, Minus, Flame, Calendar, Activity } from 'lucide-vue-next'
import ApexChart from 'vue3-apexcharts'
import UpgradePrompt from '@/Components/UpgradePrompt.vue'

const props = defineProps({
  today_mood: Object,
  weekly_moods: Array,
  monthly_moods: Array,
  mood_habit_correlation: Array,
  average_this_week: Number,
  streak: Number,
  daily_affirmation: String,
  correlation_locked: Boolean
})

const trend = computed(() => {
  if (props.weekly_moods.length < 2) return 'stable'
  const lastTwo = props.weekly_moods.slice(-2)
  if (lastTwo[1].score > lastTwo[0].score) return 'up'
  if (lastTwo[1].score < lastTwo[0].score) return 'down'
  return 'stable'
})

const chartOptions = {
  chart: {
    height: 350,
    type: 'line',
    toolbar: { show: false },
    zoom: { enabled: false }
  },
  colors: ['#6366f1', '#f59e0b'],
  dataLabels: { enabled: false },
  stroke: {
    width: [4, 4],
    curve: 'smooth',
    dashArray: [0, 8]
  },
  title: {
    text: 'Mood vs. Habit Completion',
    align: 'left',
    style: { fontSize: '16px', fontWeight: 600 }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'right'
  },
  xaxis: {
    type: 'datetime',
    categories: props.mood_habit_correlation.map(d => d.date),
    labels: {
      format: 'MMM dd',
      style: { colors: '#94a3b8' }
    }
  },
  yaxis: [
    {
      title: { text: 'Mood Score' },
      min: 1,
      max: 5,
      tickAmount: 4,
      labels: { formatter: (val) => val.toFixed(1) }
    },
    {
      opposite: true,
      title: { text: 'Completion Rate %' },
      min: 0,
      max: 100,
      tickAmount: 5,
      labels: { formatter: (val) => `${val}%` }
    }
  ],
  tooltip: {
    shared: true,
    intersect: false,
    x: { format: 'dd MMM yyyy' }
  },
  grid: {
    borderColor: '#f1f5f9',
    strokeDashArray: 4
  }
}

const series = [
  {
    name: 'Mood Score',
    type: 'line',
    data: props.mood_habit_correlation.map(d => d.mood_score)
  },
  {
    name: 'Completion Rate',
    type: 'line',
    data: props.mood_habit_correlation.map(d => d.completion_rate)
  }
]

const getDayEmoji = (date) => {
  const day = props.weekly_moods.find(m => m.logged_date.startsWith(date))
  return day ? day.emoji : '—'
}

const getScoreColor = (score) => {
  if (!score) return 'bg-slate-100 dark:bg-slate-800 text-slate-400'
  if (score <= 2) return 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
  if (score === 3) return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400'
  return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'
}

import dayjs from 'dayjs'
import weekday from 'dayjs/plugin/weekday'
import isToday from 'dayjs/plugin/isToday'
dayjs.extend(weekday)
dayjs.extend(isToday)

const weekDays = computed(() => {
    const days = []
    // Get start of current week (Monday)
    const startOfWeek = dayjs().startOf('week').add(1, 'day')

    for (let i = 0; i < 7; i++) {
        const date     = startOfWeek.add(i, 'day')
        const dateStr  = date.format('YYYY-MM-DD')
        const moodLog  = props.weekly_moods.find(m => m.logged_date?.startsWith(dateStr))

        days.push({
            date:      dateStr,
            dayLabel:  date.format('ddd'),    // Mon, Tue...
            dayNum:    date.format('D'),       // 1, 2...
            isToday:   date.isToday(),
            mood:      moodLog ?? null,
        })
    }
    return days
})
</script>

<template>
  <AppLayout title="Mood Tracking" subtitle="Understand your mind, master your habits">
    <div class="max-w-7xl mx-auto space-y-8">
      <!-- Section 1: Today -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <MoodCheckIn :current-mood="today_mood" />
        <div class="space-y-6">
          <AffirmationCard 
            v-if="daily_affirmation"
            :affirmation="daily_affirmation" 
            :generated-at="today_mood?.created_at"
          />
          
          <Card class="bg-indigo-600 text-white overflow-hidden relative">
            <div class="absolute -right-8 -bottom-8 opacity-10">
              <Flame :size="160" />
            </div>
            <CardContent class="p-6">
              <div class="flex items-center gap-4">
                <div class="h-16 w-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center">
                  <Flame class="h-8 w-8 text-white" />
                </div>
                <div>
                  <p class="text-indigo-100 text-sm font-medium uppercase tracking-wider">Logging Streak</p>
                  <h3 class="text-4xl font-black">{{ streak }} Days</h3>
                </div>
              </div>
              <p class="mt-4 text-indigo-100 text-sm italic">
                Consistency is the key to self-awareness. Keep showing up!
              </p>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Section 2: This Week -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-7">
          <div>
            <CardTitle>This Week's Reflection</CardTitle>
            <CardDescription>Your mood pattern over the last 7 days</CardDescription>
          </div>
          <div class="flex items-center gap-3">
             <div class="text-right">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-tighter">Weekly Avg</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white">{{ average_this_week }}/5</p>
             </div>
             <Badge :class="getScoreColor(Math.round(average_this_week))" class="h-10 px-3 text-sm gap-1">
                <TrendingUp v-if="trend === 'up'" class="h-4 w-4" />
                <TrendingDown v-else-if="trend === 'down'" class="h-4 w-4" />
                <Minus v-else class="h-4 w-4" />
                {{ trend === 'up' ? 'Trending Up' : trend === 'down' ? 'Trending Down' : 'Stable' }}
             </Badge>
          </div>
        </CardHeader>
          <div class="grid grid-cols-7 gap-2">
            <div v-for="day in weekDays" :key="day.date"
                :class="['flex flex-col items-center gap-1 p-2 rounded-xl transition-all',
                         day.isToday ? 'bg-indigo-50 dark:bg-indigo-900/20 ring-1 ring-indigo-200 dark:ring-indigo-800' : '']">

                <!-- Day label -->
                <span class="text-xs text-gray-400 dark:text-gray-500 font-medium uppercase">
                    {{ day.dayLabel }}
                </span>

                <!-- Mood emoji or empty circle -->
                <div v-if="day.mood"
                    class="text-2xl cursor-default hover:scale-110 transition-transform"
                    :title="day.mood.label">
                    {{ day.mood.emoji }}
                </div>
                <div v-else
                    class="w-10 h-10 rounded-full border-2 border-dashed
                           border-gray-200 dark:border-gray-700 flex items-center
                           justify-center text-gray-300 dark:text-gray-600 text-xs shadow-sm">
                    —
                </div>

                <!-- Day number -->
                <span :class="['text-xs font-bold mt-1',
                    day.isToday
                        ? 'text-indigo-600 dark:text-indigo-400'
                        : 'text-gray-400 dark:text-gray-500']">
                    {{ day.dayNum }}
                </span>
            </div>
          </div>
      </Card>

      <!-- Section 3: Correlation -->
      <Card class="relative overflow-hidden group border-gray-100 dark:border-gray-800">
        <div v-if="correlation_locked" class="absolute inset-0 bg-gray-50/10 dark:bg-gray-950/10 backdrop-blur-[6px] z-10 flex flex-col items-center justify-center p-8 text-center transition-all duration-500">
            <div class="p-6 rounded-3xl bg-white/90 dark:bg-gray-900/90 shadow-2xl border border-indigo-500/10 max-w-lg mx-auto transform transition-transform duration-500 hover:scale-[1.02]">
                <UpgradePrompt 
                    feature="Mood-Habit Correlation"
                    message="Gain deep insights into how your emotions influence your productivity over time. Upgrade to Pro to unlock this advanced analytical tool."
                    requiredPlan="pro"
                    class="bg-transparent border-none shadow-none p-0"
                />
            </div>
        </div>

        <CardContent :class="['p-6 transition-all duration-700', correlation_locked ? 'opacity-20 grayscale pointer-events-none' : '']">
          <div v-if="mood_habit_correlation.filter(d => d.mood_score).length >= 3 || correlation_locked">
            <ApexChart 
              type="line" 
              height="350" 
              :options="chartOptions" 
              :series="correlation_locked ? [{name: 'Sample', data: [3,4,2,5,3,4,4]}] : series" 
            />
          </div>
          <div v-else class="py-20 flex flex-col items-center justify-center text-center space-y-4">
            <div class="h-20 w-20 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center border border-slate-100 dark:border-slate-800">
                <Activity class="h-10 w-10 text-slate-300" />
            </div>
            <div>
              <h4 class="text-lg font-semibold text-slate-900 dark:text-white">Awaiting Data Insights</h4>
              <p class="text-slate-500 max-w-sm mx-auto">
                Log your mood for at least 3 days to unlock the habit correlation analysis. 
                Discover how your emotions drive your productivity!
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
