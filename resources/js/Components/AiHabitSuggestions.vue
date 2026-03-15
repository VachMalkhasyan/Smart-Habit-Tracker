<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Sparkles, ArrowRight, RotateCcw } from 'lucide-vue-next'

const page = usePage()
const userName = computed(() => page.props.auth.user?.name || 'You')

const suggestions = ref([])
const isLoading = ref(false)
const hasError = ref(false)
const hasGenerated = ref(false)

const generateSuggestions = async () => {
    isLoading.value = true
    hasError.value = false
    hasGenerated.value = true
    
    try {
        const { data } = await axios.get('/ai/suggest-habits')
        
        let fetched = []
        if (Array.isArray(data)) {
            fetched = data
        } else if (data.suggestions && Array.isArray(data.suggestions)) {
            fetched = data.suggestions
        }
        
        // Ensure uniquely keyable results for removing correctly
        suggestions.value = fetched.map(s => ({ ...s, id: Math.random().toString(36).substring(7) }))
    } catch (e) {
        hasError.value = true
        console.error('Failed to generate suggestions:', e)
    } finally {
        isLoading.value = false
    }
}

const skipHabit = (id) => {
    suggestions.value = suggestions.value.filter(s => s.id !== id)
}

const addHabit = (suggestion) => {
    const params = new URLSearchParams()
    if (suggestion.name) params.append('name', suggestion.name)
    if (suggestion.category) params.append('category', suggestion.category)
    if (suggestion.goal) params.append('goal', suggestion.goal)
    if (suggestion.goal_unit) params.append('goal_unit', String(suggestion.goal_unit))
    if (suggestion.priority) params.append('priority', suggestion.priority)

    router.visit(route('habits.create') + '?' + params.toString())
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header Card -->
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-2xl p-6 md:p-8 text-white shadow-lg flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <h2 class="text-2xl font-bold mb-2">Personalized for {{ userName }} ✨</h2>
                <p class="text-indigo-100 mb-1">Based on your habits, streaks, goals and weak days</p>
                <p class="text-indigo-200/80 text-xs">Uses your real data — not generic templates</p>
            </div>
            <button @click="generateSuggestions" :disabled="isLoading"
                    class="shrink-0 px-6 py-3 bg-white/10 hover:bg-white/20 border border-white/30 rounded-xl font-medium transition-all flex items-center gap-2">
                <Sparkles class="w-4 h-4" />
                <span>{{ hasGenerated && !hasError && !isLoading ? 'Regenerate ↺' : 'Generate Suggestions' }}</span>
            </button>
        </div>

        <!-- Initial State -->
        <div v-if="!hasGenerated && !isLoading && !hasError" class="py-16 text-center border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-2xl flex flex-col items-center justify-center">
            <div class="text-6xl mb-6">✨</div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Ready to suggest habits built around YOUR life</h3>
            <button @click="generateSuggestions" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium shadow-md hover:shadow-lg transition-all text-lg">
                Generate My Suggestions
            </button>
        </div>

        <!-- Loading State -->
        <div v-else-if="isLoading" class="space-y-6">
            <p class="text-center font-medium text-indigo-600 dark:text-indigo-400 py-2 animate-pulse">
                Analyzing your habits, streaks and goals...
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="i in 4" :key="i" class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-indigo-900/50 rounded-2xl p-5 animate-pulse min-h-[160px]">
                    <div class="h-5 bg-gray-200 dark:bg-gray-800 rounded w-1/2 mb-4"></div>
                    <div class="h-4 bg-gray-100 dark:bg-gray-800 rounded w-full mb-2"></div>
                    <div class="h-4 bg-gray-100 dark:bg-gray-800 rounded w-4/5 mb-6"></div>
                    <div class="flex gap-2">
                        <div class="h-9 bg-gray-200 dark:bg-gray-800 rounded w-1/2"></div>
                        <div class="h-9 bg-gray-100 dark:bg-gray-800 rounded w-1/4"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="hasError" class="py-12 text-center bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-2xl">
            <p class="text-red-600 dark:text-red-400 font-medium mb-4">Couldn't generate suggestions right now.</p>
            <button @click="generateSuggestions" class="text-sm border border-red-200 dark:border-red-800 px-4 py-2 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 text-red-700 dark:text-red-300 transition-colors">
                Try again ↺
            </button>
        </div>

        <!-- Results Grid -->
        <div v-else-if="suggestions.length > 0">
            <TransitionGroup name="list" tag="div" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="suggestion in suggestions" :key="suggestion.id"
                     class="group bg-white dark:bg-gray-900 rounded-2xl p-5 border-2 border-indigo-50 dark:border-indigo-900/30 hover:border-indigo-200 dark:hover:border-indigo-700 shadow-sm transition-all flex flex-col h-full">
                    
                    <div class="flex flex-col mb-3">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 line-clamp-1">
                            {{ suggestion.name || 'Untitled Habit' }}
                        </h3>
                        <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400 mt-1">
                            {{ suggestion.category || 'General' }} • {{ suggestion.goal }} {{ suggestion.goal_unit }}
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic mb-6 flex-1">
                        "{{ suggestion.reason }}"
                    </p>
                    
                    <div class="flex items-center gap-3 mt-auto">
                        <button @click="addHabit(suggestion)"
                                class="flex-1 flex items-center justify-center text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 py-2.5 rounded-xl transition-colors">
                            <span>Add This Habit &rarr;</span>
                        </button>
                        <button @click="skipHabit(suggestion.id)"
                                class="px-4 py-2.5 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors shrink-0">
                            Skip
                        </button>
                    </div>
                </div>
            </TransitionGroup>
            
            <div class="text-center mt-8">
                <button @click="generateSuggestions" class="inline-flex items-center text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 px-4 py-2 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                    <RotateCcw class="w-4 h-4 mr-2" />
                    Regenerate ↺
                </button>
            </div>
        </div>
        
        <!-- Empty Results -->
        <div v-else-if="hasGenerated && !isLoading && suggestions.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
            No suggestions found. Try regenerating.
        </div>
    </div>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
