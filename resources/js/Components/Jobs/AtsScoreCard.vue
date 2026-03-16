<script setup>
import { ref, onMounted, computed } from 'vue'

const props = defineProps({
    application: Object,
    compact: Boolean,
    hasCV: Boolean,
})

const emit = defineEmits(['analyze'])

const analyzing = ref(false)
const displayedScore = ref(0)

const score = computed(() => props.application?.ats_score || 0)
const analysis = computed(() => {
    const raw = props.application?.ats_analysis
    if (!raw) return {}
    // Handle case where it comes as string instead of object
    if (typeof raw === 'string') {
        try { 
            return JSON.parse(raw) 
        } catch (e) { 
            console.error('Failed to parse ats_analysis:', e)
            return {} 
        }
    }
    return raw
})

const scoreColor = (s) => {
    if (s >= 75) return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800'
    if (s >= 60) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800'
    if (s >= 40) return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 border-orange-200 dark:border-orange-800'
    return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800'
}

const verdictConfig = {
    'Perfect Match':          { color: 'text-green-600 dark:text-green-400',  icon: '🌟' },
    'Strong Match':           { color: 'text-green-500 dark:text-green-400',  icon: '✅' },
    'Good Match':             { color: 'text-blue-500 dark:text-blue-400',   icon: '👍' },
    'Fair Match':             { color: 'text-yellow-500 dark:text-yellow-400', icon: '⚠️' },
    'Weak Match':             { color: 'text-red-500 dark:text-red-400',    icon: '❌' },
    'Apply confidently':      { color: 'text-green-600 dark:text-green-400',  icon: '🚀' },
    'Apply with adjustments': { color: 'text-yellow-600 dark:text-yellow-400', icon: '✏️' },
    'Consider carefully':     { color: 'text-red-500 dark:text-red-400',    icon: '🤔' },
}

const getVerdictStyle = (v) => verdictConfig[v] || { color: 'text-gray-600', icon: '📋' }

const animateScore = () => {
    if (props.compact) {
        displayedScore.value = score.value
        return
    }
    
    displayedScore.value = 0
    const target = score.value
    if (target === 0) return
    
    const duration = 1000
    const start = performance.now()
    
    const step = (timestamp) => {
        const progress = Math.min((timestamp - start) / duration, 1)
        displayedScore.value = Math.floor(progress * target)
        if (progress < 1) {
            window.requestAnimationFrame(step)
        }
    }
    window.requestAnimationFrame(step)
}

onMounted(() => {
    animateScore()
})

const handleAnalyze = () => {
    analyzing.value = true
    emit('analyze')
}
</script>

<template>
    <!-- Compact Version (Badge) -->
    <div v-if="compact">
        <div v-if="application.ats_score !== null"
            :class="scoreColor(application.ats_score)"
            class="text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded-full border">
            ATS {{ application.ats_score }}%
        </div>
        <button v-else-if="hasCV"
            @click.stop="handleAnalyze"
            class="text-[10px] uppercase tracking-wider font-bold text-indigo-500 hover:text-indigo-600 flex items-center gap-1">
            <span>✨</span> Score fit
        </button>
    </div>

    <!-- Full Version (Card) -->
    <div v-else class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6 shadow-sm overflow-hidden relative">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="text-indigo-500">🎯</span> ATS Match Score
            </h3>
            <div v-if="application.ats_analyzed_at" class="text-[10px] text-gray-400 uppercase tracking-tighter">
                Analyzed: {{ new Date(application.ats_analyzed_at).toLocaleDateString() }}
            </div>
        </div>

        <div v-if="application.ats_score !== null" class="space-y-6">
            <div class="flex flex-col items-center justify-center text-center">
                <div class="text-5xl font-black mb-2" :class="getVerdictStyle(analysis.verdict).color">
                    {{ displayedScore }}<span class="text-2xl opacity-50">%</span>
                </div>
                <div class="font-bold flex items-center gap-1" :class="getVerdictStyle(analysis.verdict).color">
                    {{ getVerdictStyle(analysis.verdict).icon }} {{ analysis.verdict }}
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="h-2 w-full bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                <div class="h-full transition-all duration-1000 ease-out rounded-full"
                    :class="score >= 75 ? 'bg-green-500' : (score >= 60 ? 'bg-yellow-500' : (score >= 40 ? 'bg-orange-500' : 'bg-red-500'))"
                    :style="{ width: displayedScore + '%' }">
                </div>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 italic leading-relaxed text-center">
                "{{ analysis.summary }}"
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <h4 class="text-[10px] uppercase tracking-widest font-bold text-green-600 dark:text-green-500 flex items-center gap-1">
                        ✅ Matching Skills
                    </h4>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="skill in (analysis.matching_skills ?? [])" :key="skill"
                            class="text-[10px] px-2 py-0.5 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-md border border-green-100 dark:border-green-800">
                            {{ skill }}
                        </span>
                        <span v-if="!(analysis.matching_skills?.length)" class="text-xs text-gray-400">None detected</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <h4 class="text-[10px] uppercase tracking-widest font-bold text-red-600 dark:text-red-500 flex items-center gap-1">
                        ❌ Missing Skills
                    </h4>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="skill in (analysis.missing_skills ?? [])" :key="skill"
                            class="text-[10px] px-2 py-0.5 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-md border border-red-100 dark:border-red-800">
                            {{ skill }}
                        </span>
                        <span v-if="!(analysis.missing_skills?.length)" class="text-xs text-gray-400">None identified</span>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl space-y-3">
                <h4 class="text-[10px] uppercase tracking-widest font-bold text-gray-500 dark:text-gray-400 flex items-center gap-1">
                    💡 CV Tips for This Role
                </h4>
                <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-2">
                    <li v-for="tip in (analysis.cv_tips_for_this_role ?? [])" :key="tip" class="flex gap-2">
                        <span class="text-indigo-400">•</span> {{ tip }}
                    </li>
                </ul>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-gray-50 dark:border-gray-800">
                <div class="text-xs font-semibold" :class="getVerdictStyle(analysis.recommendation).color">
                    {{ getVerdictStyle(analysis.recommendation).icon }} Recommendation: {{ analysis.recommendation }}
                </div>
                <button @click="handleAnalyze" 
                    class="text-xs text-gray-400 hover:text-indigo-500 flex items-center gap-1 transition-colors">
                    <span :class="{ 'animate-spin': analyzing }">↺</span> Re-analyze
                </button>
            </div>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-10 text-center space-y-4">
            <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/20 rounded-full flex items-center justify-center text-3xl">
                🤖
            </div>
            <div>
                <h4 class="font-bold text-gray-900 dark:text-white">Ready for Analysis</h4>
                <p class="text-sm text-gray-500 mb-6">Compare your active CV with this job role description.</p>
                <button v-if="hasCV"
                    @click="handleAnalyze"
                    :disabled="analyzing"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-indigo-700 disabled:opacity-50 transition-all flex items-center gap-2">
                    <span v-if="analyzing" class="animate-spin text-lg">⏳</span>
                    <span v-else>✨</span>
                    {{ analyzing ? 'Analyzing...' : 'Analyze Job Fit' }}
                </button>
                <div v-else class="text-amber-600 dark:text-amber-400 text-xs font-medium max-w-xs mx-auto">
                    ⚠️ You need to upload a CV first to use ATS scoring.
                </div>
            </div>
        </div>
    </div>
</template>
