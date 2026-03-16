<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Check, Edit2, Plus, Smile, ChevronRight, ChevronLeft, Sparkles } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  currentMood: {
    type: Object,
    default: null
  },
  compact: {
    type: Boolean,
    default: false
  }
})

const { toast } = useToast()
const currentStep = ref(props.currentMood ? 3 : 1) // 1: Emoji, 2: Details, 3: Success/Summary
const showSuccessAnimation = ref(false)

const moods = [
  { score: 1, emoji: '😢', label: 'Terrible', color: 'text-red-500', bg: 'bg-red-50 dark:bg-red-950/30' },
  { score: 2, emoji: '😕', label: 'Bad', color: 'text-orange-500', bg: 'bg-orange-50 dark:bg-orange-950/30' },
  { score: 3, emoji: '😐', label: 'Okay', color: 'text-yellow-500', bg: 'bg-yellow-50 dark:bg-yellow-950/30' },
  { score: 4, emoji: '🙂', label: 'Good', color: 'text-green-500', bg: 'bg-green-50 dark:bg-green-950/30' },
  { score: 5, emoji: '😄', label: 'Amazing', color: 'text-emerald-500', bg: 'bg-emerald-50 dark:bg-emerald-950/30' }
]

const predefinedTags = [
  { label: '😴 Tired', value: 'tired' },
  { label: '💼 Work stress', value: 'work' },
  { label: '🏋️ Active', value: 'active' },
  { label: '😰 Anxious', value: 'anxious' },
  { label: '🎯 Focused', value: 'focused' },
  { label: '❤️ Grateful', value: 'grateful' },
  { label: '🤒 Sick', value: 'sick' },
  { label: '🎉 Excited', value: 'excited' }
]

const form = useForm({
  score: props.currentMood?.score || 3,
  note: props.currentMood?.note || '',
  tags: props.currentMood?.tags || []
})

const selectedMood = computed(() => moods.find(m => m.score == form.score))

const toggleTag = (tag) => {
  const index = form.tags.indexOf(tag)
  if (index === -1) {
    form.tags.push(tag)
  } else {
    form.tags.splice(index, 1)
  }
}

const nextStep = () => {
    if (currentStep.value < 3) currentStep.value++
}

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--
}

const selectMood = (score) => {
    form.score = score
    nextStep()
}

const submit = () => {
  form.post(route('mood.store'), {
    preserveScroll: true,
    onSuccess: () => {
      currentStep.value = 3
      showSuccessAnimation.value = true
      toast({
        title: 'Mood Saved ✨',
        description: 'Your daily check-in is complete.'
      })
    }
  })
}

const reset = () => {
    currentStep.value = 1
}
</script>

<template>
  <div class="h-full">
    <Card :class="[
        'overflow-hidden transition-all duration-500 border-none shadow-md',
        compact ? 'bg-white dark:bg-slate-950' : 'h-full bg-white dark:bg-slate-900'
    ]">
      <CardContent class="p-0">
        <!-- STEP 1: EMOJI SELECTION -->
        <Transition
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="opacity-0 translate-x-4"
          enter-to-class="opacity-100 translate-x-0"
          leave-active-class="transition duration-300 ease-in"
          leave-from-class="opacity-100 translate-x-0"
          leave-to-class="opacity-0 -translate-x-4"
          mode="out-in"
        >
          <div v-if="currentStep === 1" class="p-6 space-y-4">
            <div class="flex items-center gap-2 mb-2">
                <Smile class="h-4 w-4 text-indigo-500" />
                <h3 class="font-bold text-slate-800 dark:text-slate-200">How are you feeling today?</h3>
            </div>
            <div class="flex justify-between items-center px-1 py-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl">
                <button
                v-for="m in moods"
                :key="m.score"
                @click="selectMood(m.score)"
                class="flex flex-col items-center gap-2 transition-all duration-300 hover:scale-125 focus:outline-none group"
                >
                <span class="text-4xl filter grayscale-[20%] group-hover:grayscale-0">{{ m.emoji }}</span>
                </button>
            </div>
            <p class="text-[10px] text-center text-slate-400 font-medium uppercase tracking-widest">Select an emoji to continue</p>
          </div>

          <!-- STEP 2: DETAILS -->
          <div v-else-if="currentStep === 2" class="p-6 space-y-5">
            <div class="flex items-center justify-between mb-2">
                <button @click="prevStep" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full text-slate-500">
                    <ChevronLeft class="h-4 w-4" />
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-2xl">{{ selectedMood.emoji }}</span>
                    <span class="font-bold text-sm" :class="selectedMood.color">Feeling {{ selectedMood.label }}</span>
                </div>
                <div class="w-6"></div>
            </div>

            <div class="space-y-3">
              <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">What's on your mind?</p>
              <Textarea 
                v-model="form.note" 
                placeholder="Brief note about your day..."
                class="resize-none min-h-[80px] bg-slate-50/50 dark:bg-slate-950 border-none shadow-inner text-sm"
              />
            </div>

            <div class="space-y-3">
              <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Any tags?</p>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="tag in predefinedTags"
                  :key="tag.value"
                  @click="toggleTag(tag.value)"
                  class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-all border shrink-0"
                  :class="[
                    form.tags.includes(tag.value)
                      ? 'bg-indigo-500 border-indigo-500 text-white shadow-sm'
                      : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-500 hover:border-indigo-300'
                  ]"
                >
                  {{ tag.label }}
                </button>
              </div>
            </div>

            <Button 
                @click="submit" 
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-500/20 font-bold py-5"
                :disabled="form.processing"
            >
                <span v-if="!form.processing">Finish Logging</span>
                <span v-else class="flex items-center gap-2"><Sparkles class="h-4 w-4 animate-spin" /> Saving...</span>
            </Button>
          </div>

          <!-- STEP 3: SUCCESS / SUMMARY -->
          <div v-else-if="currentStep === 3" class="p-6">
            <div v-if="currentMood || showSuccessAnimation" class="flex flex-col items-center text-center space-y-4">
                <div class="relative">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-4xl shadow-inner border border-slate-100 dark:border-slate-800" :class="selectedMood?.bg || 'bg-indigo-50'">
                        {{ selectedMood?.emoji || currentMood?.emoji }}
                    </div>
                    <div class="absolute -top-1 -right-1 bg-emerald-500 text-white rounded-full p-1 shadow-sm">
                        <Check class="h-3 w-3" />
                    </div>
                </div>
                <div>
                   <h3 class="font-bold text-slate-800 dark:text-slate-100">Daily Mood Logged</h3>
                   <p class="text-xs text-slate-500 mt-1">Consistency is key! You've logged your mood for today.</p>
                </div>
                
                <div v-if="form.note" class="w-full p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl text-xs italic text-slate-600 dark:text-slate-400">
                    "{{ form.note }}"
                </div>

                <Button variant="ghost" size="sm" @click="reset" class="text-[10px] font-bold uppercase tracking-widest text-indigo-500 hover:text-indigo-600">
                    <Edit2 class="h-3 w-3 mr-1" /> Update Log
                </Button>
            </div>
          </div>
        </Transition>
      </CardContent>
    </Card>
  </div>
</template>

<style scoped>
.v-enter-active,
.v-leave-active {
  transition: opacity 0.3s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}
</style>
