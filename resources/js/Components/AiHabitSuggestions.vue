<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { Sparkles, ArrowRight, Bot, Target } from 'lucide-vue-next'

const suggestions = ref([])
const isLoading = ref(false)
const hasError = ref(false)
const hasGenerated = ref(false)

const generateSuggestions = async () => {
    isLoading.value = true
    hasError.value = false
    
    try {
        const { data } = await axios.get('/ai/suggest-habits')
        
        // Ensure data is array
        if (Array.isArray(data)) {
            suggestions.value = data
        } else if (data.suggestions && Array.isArray(data.suggestions)) {
            suggestions.value = data.suggestions
        } else {
            throw new Error('Invalid format')
        }
        
        hasGenerated.value = true
    } catch (e) {
        hasError.value = true
        console.error('Failed to generate suggestions:', e)
    } finally {
        isLoading.value = false
    }
}

const addHabit = (suggestion) => {
    router.visit(route('habits.create'), {
        data: {
            name: suggestion.name,
            category: suggestion.category || 'Other',
            description: suggestion.reason || ''
        }
    })
}
</script>

<template>
    <div class="bg-indigo-50/50 dark:bg-indigo-900/10 rounded-2xl border border-indigo-100 dark:border-indigo-800/30 p-6 md:p-8">
        
        <!-- Header & Initial State -->
        <div class="text-center max-w-2xl mx-auto mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-indigo-100 dark:bg-indigo-900/50 rounded-full mb-4 text-indigo-600 dark:text-indigo-400">
                <Bot class="w-6 h-6" />
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Personalized AI Suggestions
            </h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm md:text-base">
                Your AI Coach will analyze your current habits, streaks, and goals to recommend the perfect new habits to add to your routine.
            </p>
        </div>

        <!-- CTA / Error -->
        <div v-if="!hasGenerated || hasError" class="flex flex-col items-center justify-center pb-4">
            <button 
                @click="generateSuggestions"
                :disabled="isLoading"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-base font-medium text-white transition-all duration-200 bg-indigo-600 border border-transparent rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600 disabled:opacity-75 disabled:cursor-not-allowed shadow-md hover:shadow-lg">
                <span v-if="isLoading" class="absolute left-4">
                    <div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                </span>
                <Sparkles v-if="!isLoading" class="w-5 h-5 mr-2 -ml-1 text-indigo-200 group-hover:text-white" />
                <span>{{ isLoading ? 'Analyzing profile...' : 'Generate suggestions for me ✨' }}</span>
            </button>
            
            <p v-if="hasError" class="mt-4 text-sm font-medium text-red-500 dark:text-red-400 text-center bg-red-50 dark:bg-red-900/20 px-4 py-2 rounded-md">
                Couldn't generate suggestions right now. Try again.
            </p>
        </div>

        <!-- Loading Skeleton Grid -->
        <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="i in 3" :key="i" class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 animate-pulse shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="h-6 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-5 w-16 bg-gray-100 dark:bg-gray-700 rounded-full"></div>
                </div>
                <div class="space-y-2 mb-6">
                    <div class="h-4 bg-gray-100 dark:bg-gray-700 rounded w-full"></div>
                    <div class="h-4 bg-gray-100 dark:bg-gray-700 rounded w-5/6"></div>
                </div>
                <div class="h-10 bg-gray-100 dark:bg-gray-700 rounded-lg w-full"></div>
            </div>
        </div>

        <!-- Results Grid -->
        <div v-else-if="suggestions.length > 0" class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="(suggestion, index) in suggestions" :key="index"
                     class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-md transition-all flex flex-col h-full">
                    
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 line-clamp-2">
                            {{ suggestion.name || 'Untitled Habit' }}
                        </h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 ml-2 whitespace-nowrap">
                            <Target class="w-3 h-3 mr-1" />
                            {{ suggestion.category || 'General' }}
                        </span>
                    </div>
                    
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic mb-6 flex-1 line-clamp-3">
                        "{{ suggestion.reason }}"
                    </p>
                    
                    <button @click="addHabit(suggestion)"
                            class="w-full mt-auto flex items-center justify-center text-sm font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 py-2.5 rounded-lg transition-colors">
                        <span>Add this habit</span>
                        <ArrowRight class="w-4 h-4 ml-1.5 opacity-70 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" />
                    </button>
                </div>
            </div>
            
            <div class="text-center pt-2">
                <button @click="generateSuggestions" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center justify-center mx-auto focus:outline-none focus:underline">
                    <Sparkles class="w-4 h-4 mr-1 pb-0.5" />
                    Regenerate suggestions
                </button>
            </div>
        </div>
    </div>
</template>
