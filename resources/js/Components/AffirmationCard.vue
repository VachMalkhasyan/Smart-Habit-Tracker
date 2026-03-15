<script setup>
import { ref } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { RefreshCcw, Sparkles, Quote } from 'lucide-vue-next'
import axios from 'axios'

const props = defineProps({
  affirmation: {
    type: String,
    required: true
  },
  generatedAt: {
    type: String,
    default: null
  }
})

const currentAffirmation = ref(props.affirmation)
const isRegenerating = ref(false)
const lastGeneratedAt = ref(props.generatedAt ? new Date(props.generatedAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '9:00 AM')

const regenerate = async () => {
  if (isRegenerating.value) return
  
  isRegenerating.value = true
  try {
    const response = await axios.post(route('mood.affirmation.regenerate'))
    currentAffirmation.value = response.data.affirmation
    lastGeneratedAt.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  } catch (error) {
    // Fail silently or handle UI error
  } finally {
    isRegenerating.value = false
  }
}
</script>

<template>
  <Card class="relative overflow-hidden border-none shadow-md bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/20 dark:to-orange-950/10">
    <!-- Decorative background elements -->
    <div class="absolute -top-6 -right-6 text-amber-200/40 dark:text-amber-500/10 rotate-12">
      <Sparkles :size="80" />
    </div>
    
    <CardContent class="p-6 relative">
      <div class="flex items-center gap-2 mb-4">
        <div class="p-1.5 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
          <Sparkles class="h-4 w-4 text-amber-600 dark:text-amber-400" />
        </div>
        <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-300">Daily Inspiration</span>
      </div>

      <div class="relative min-h-[100px] flex items-center justify-center py-2 px-4">
        <Quote class="absolute top-0 left-0 h-6 w-6 text-amber-200 dark:text-amber-800 rotate-180" />
        
        <Transition
          enter-active-class="transition duration-500 ease-out"
          enter-from-class="opacity-0 translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-300 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
          mode="out-in"
        >
          <p 
            :key="currentAffirmation"
            class="text-lg md:text-xl font-medium italic text-slate-800 dark:text-slate-200 leading-relaxed text-center"
          >
            "{{ currentAffirmation }}"
          </p>
        </Transition>

        <Quote class="absolute bottom-0 right-0 h-6 w-6 text-amber-200 dark:text-amber-800" />
      </div>

      <div class="mt-6 flex items-center justify-between">
        <div class="text-[10px] text-amber-600/60 dark:text-amber-400/40 font-medium">
          Refined at {{ lastGeneratedAt }}
        </div>
        
        <Button 
          variant="ghost" 
          size="sm" 
          @click="regenerate"
          class="h-8 text-[11px] gap-1.5 text-amber-700 hover:text-amber-800 hover:bg-amber-100/50 dark:text-amber-300 dark:hover:bg-amber-900/40 transition-all font-bold"
          :disabled="isRegenerating"
        >
          <RefreshCcw class="h-3 w-3" :class="{ 'animate-spin': isRegenerating }" />
          {{ isRegenerating ? 'REFINING...' : 'REGENERATE' }}
        </Button>
      </div>
    </CardContent>
    
    <!-- Animated bottom border -->
    <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-amber-400 to-orange-500 w-full opacity-50" />
  </Card>
</template>
