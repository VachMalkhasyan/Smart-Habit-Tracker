<script setup>
import { computed } from 'vue'
import { 
    Sparkles, 
    CheckCircle2, 
    AlertCircle, 
    Lightbulb, 
    Trophy, 
    TrendingDown, 
    Calendar,
    RefreshCw,
    Loader2
} from 'lucide-vue-next'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(relativeTime)

const props = defineProps({
    summary: {
        type: Object,
        default: null
    },
    loading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['regenerate'])

const lastGenerated = computed(() => {
    if (!props.summary?.generated_at) return ''
    return dayjs(props.summary.generated_at).fromNow()
})

const dateRange = computed(() => {
    if (!props.summary?.generated_at) return ''
    const generatedDate = dayjs(props.summary.generated_at)
    const start = generatedDate.clone().subtract(7, 'days')
    return `${start.format('MMM D')} – ${generatedDate.format('MMM D')}`
})
</script>

<template>
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Top accent gradient -->
        <div class="h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

        <!-- Header -->
        <div class="p-4 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between bg-gray-50/30 dark:bg-gray-800/50">
            <div class="flex items-center gap-2">
                <Sparkles class="w-5 h-5 text-indigo-500" />
                <h3 class="font-bold text-gray-900 dark:text-white">Weekly AI Summary</h3>
                <span v-if="summary" class="text-xs text-gray-500 dark:text-gray-400 font-normal ml-2">
                    {{ dateRange }}
                </span>
            </div>
            <button v-if="summary && !loading" 
                    @click="emit('regenerate')"
                    class="text-xs flex items-center gap-1.5 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors group">
                <RefreshCw class="w-3.5 h-3.5 group-hover:rotate-180 transition-transform duration-500" />
                Regenerate
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="p-8 space-y-6">
            <div class="flex flex-col items-center justify-center space-y-4 text-center">
                <div class="relative">
                    <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/20 rounded-full flex items-center justify-center">
                        <Loader2 class="w-8 h-8 text-indigo-500 animate-spin" />
                    </div>
                </div>
                <div>
                    <h4 class="text-base font-bold text-gray-900 dark:text-white mb-1">Crunching the numbers...</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 animate-pulse font-medium">
                        Analyzing your streaks, patterns, and consistency
                    </p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="i in 3" :key="i" class="h-24 bg-gray-50 dark:bg-gray-700/30 rounded-2xl animate-pulse"></div>
            </div>
        </div>

        <!-- Empty State (No Summary Yet) -->
        <div v-else-if="!summary" class="p-10 text-center flex flex-col items-center justify-center">
            <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/20 rounded-full flex items-center justify-center mb-4 border border-indigo-100 dark:border-indigo-800/30">
                <Calendar class="w-8 h-8 text-indigo-500" />
            </div>
            <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Your week in review is coming</h4>
            <p class="text-gray-500 dark:text-gray-400 text-sm max-w-sm mb-6 mx-auto leading-relaxed">
                Your first AI-powered weekly summary will be ready this Monday at 9:00 AM. 
                Want to see it right now based on your current progress?
            </p>
            <button @click="emit('regenerate')" 
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-semibold shadow-lg shadow-indigo-500/20 transition-all hover:-translate-y-0.5 active:translate-y-0">
                <Sparkles class="w-4 h-4" />
                Generate Now
            </button>
        </div>

        <!-- Populated State -->
        <div v-else class="p-6 md:p-8 space-y-8 animate-fade-in">
            <!-- Headline & Overall Progress -->
            <div class="space-y-4 text-center">
                <h2 class="text-xl md:text-2xl font-black text-gray-900 dark:text-white leading-tight">
                    "{{ summary.headline }}"
                </h2>
                
                <div class="max-w-md mx-auto relative pt-1">
                    <div class="flex mb-2 items-center justify-between">
                        <div>
                            <span class="text-[10px] font-black inline-block py-1 px-2.5 uppercase rounded-full text-indigo-700 bg-indigo-100 dark:bg-indigo-900/40 dark:text-indigo-300 tracking-wider">
                                Weekly Score
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">
                                {{ summary.overall_rate }}%
                            </span>
                        </div>
                    </div>
                    <div class="overflow-hidden h-3 mb-2 text-xs flex rounded-full bg-indigo-100 dark:bg-gray-700 border border-indigo-200/20 dark:border-gray-600/20 shadow-inner">
                        <div :style="{ width: summary.overall_rate + '%' }" 
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-600 transition-all duration-1000 ease-out"></div>
                    </div>
                </div>
            </div>

            <!-- Insights Sections -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- What Went Well -->
                <div class="p-5 rounded-2xl bg-green-50 dark:bg-green-900/10 border border-green-100 dark:border-green-800/20 flex flex-col shadow-sm transition-transform hover:scale-[1.02] duration-300">
                    <div class="flex items-center gap-2 mb-3 text-green-700 dark:text-green-400">
                        <CheckCircle2 class="w-5 h-5" />
                        <span class="font-black text-[11px] tracking-widest uppercase">The Wins</span>
                    </div>
                    <p class="text-sm text-green-800 dark:text-green-200 leading-relaxed font-medium">
                        {{ summary.what_went_well }}
                    </p>
                </div>

                <!-- Needs Attention -->
                <div class="p-5 rounded-2xl bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-800/20 flex flex-col shadow-sm transition-transform hover:scale-[1.02] duration-300">
                    <div class="flex items-center gap-2 mb-3 text-amber-700 dark:text-amber-400">
                        <AlertCircle class="w-5 h-5" />
                        <span class="font-black text-[11px] tracking-widest uppercase">Struggles</span>
                    </div>
                    <p class="text-sm text-amber-800 dark:text-amber-200 leading-relaxed font-medium">
                        {{ summary.needs_attention }}
                    </p>
                </div>

                <!-- Suggestion -->
                <div class="p-5 rounded-2xl bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800/20 flex flex-col shadow-sm transition-transform hover:scale-[1.02] duration-300">
                    <div class="flex items-center gap-2 mb-3 text-blue-700 dark:text-blue-400">
                        <Lightbulb class="w-5 h-5" />
                        <span class="font-black text-[11px] tracking-widest uppercase">Coach Tip</span>
                    </div>
                    <p class="text-sm text-blue-800 dark:text-blue-200 leading-relaxed font-medium italic">
                        {{ summary.suggestion }}
                    </p>
                </div>
            </div>

            <!-- Key Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 pt-6 border-t border-gray-100 dark:border-gray-700/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-900/20 flex items-center justify-center text-orange-600 dark:text-orange-400 shrink-0 shadow-sm border border-orange-200/20">
                        <Trophy class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest leading-none mb-1">MVP Habit</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ summary.best_habit }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/20 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0 shadow-sm border border-rose-200/20">
                        <TrendingDown class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest leading-none mb-1">Needs Focus</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ summary.worst_habit }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 shadow-sm border border-emerald-200/20">
                        <Calendar class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest leading-none mb-1">Best Day</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                            {{ summary.best_day ? dayjs(summary.best_day).format('dddd, MMM D') : '—' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between pt-2 gap-4">
                <div class="text-[11px] font-medium text-gray-400 italic">
                    Generated {{ lastGenerated }}
                </div>
                <div v-if="summary" class="flex gap-2">
                     <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/30">
                        {{ summary.total_completed }} / {{ summary.total_possible }} TASKS DONE
                     </span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
