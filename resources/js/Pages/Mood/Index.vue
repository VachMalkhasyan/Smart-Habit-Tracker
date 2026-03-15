<script setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import MoodCheckIn from '@/Components/MoodCheckIn.vue'
import AffirmationCard from '@/Components/AffirmationCard.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { TrendingUp, TrendingDown, Minus, Flame, Calendar, Activity } from 'lucide-vue-next'
import ApexChart from 'vue3-apexcharts'

const props = defineProps({
  today_mood: Object,
  weekly_moods: Array,
  monthly_moods: Array,
  mood_habit_correlation: Array,
  average_this_week: Number,
  streak: Number,
  daily_affirmation: String
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

// Generate last 7 days for the weekly strip
const last7Days = Array.from({ length: 7 }, (_, i) => {
  const d = new Date()
  d.setDate(d.getDate() - (6 - i))
  return {
    date: d.toISOString().split('T')[0],
    label: d.toLocaleDateString('en-US', { weekday: 'short' })
  }
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
        <CardContent>
          <div class="grid grid-cols-7 gap-4">
            <div v-for="day in last7Days" :key="day.date" class="flex flex-col items-center gap-3">
              <span class="text-xs font-bold text-slate-400 uppercase">{{ day.label }}</span>
              <div 
                class="w-full aspect-square rounded-2xl flex items-center justify-center text-2xl transition-all hover:scale-110 cursor-default shadow-sm border"
                :class="getScoreColor(weekly_moods.find(m => m.logged_date.startsWith(day.date))?.score)"
              >
                {{ getDayEmoji(day.date) }}
              </div>
              <span v-if="weekly_moods.find(m => m.logged_date.startsWith(day.date))" class="text-[10px] font-medium text-slate-500">
                {{ weekly_moods.find(m => m.logged_date.startsWith(day.date))?.label }}
              </span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Section 3: Correlation -->
      <Card>
        <CardContent class="p-6">
          <div v-if="mood_habit_correlation.filter(d => d.mood_score).length >= 3">
            <ApexChart 
              type="line" 
              height="350" 
              :options="chartOptions" 
              :series="series" 
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
