<template>
    <DialogModal :show="show" @close="closeModal" maxWidth="3xl">
        <template #title>
            <div class="flex items-center gap-2">
                <span class="text-xl">🤖</span>
                <span>AI Interview Prep</span>
            </div>
            <div v-if="interview" class="text-sm font-normal text-gray-500 dark:text-gray-400 mt-1">
                {{ interviewTypeConfig[interview.interview_type]?.label || 'Interview' }} — {{ interview.application?.company_name }} <br/>
                <span class="text-indigo-600 dark:text-indigo-400">{{ formatDateTime(interview.scheduled_at) }}</span>
            </div>
        </template>

        <template #content>
            <div class="space-y-4">
                <div v-if="generating" class="flex flex-col items-center justify-center py-12 text-gray-400">
                    <div class="animate-pulse flex items-center gap-3 text-lg font-medium text-indigo-600 mb-6">
                        <span class="animate-spin text-2xl">⚡</span> Generating tailored interview prep...
                    </div>
                </div>

                <div v-else-if="interview?.ai_prep" class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-6 max-h-[60vh] overflow-y-auto">
                    <div class="prose dark:prose-invert max-w-none text-sm" v-html="renderedMarkdown"></div>
                </div>
                
                <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <p class="mb-4">No AI preparation generated yet.</p>
                    <button @click="regenerate" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors shadow-sm">
                        Generate Prep Now
                    </button>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-between w-full">
                <button v-if="interview?.ai_prep" @click="regenerate" :disabled="generating" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium flex items-center gap-1 transition-colors">
                    <RefreshCwIcon class="w-4 h-4" :class="{ 'animate-spin': generating }" />
                    Regenerate
                </button>
                <div v-else></div>
                <SecondaryButton @click="closeModal">Close</SecondaryButton>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import DialogModal from '@/Components/DialogModal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { RefreshCwIcon } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'
import { useJobStatus } from '@/composables/useJobStatus'
import { marked } from 'marked'

const props = defineProps({
    show: Boolean,
    interview: Object,
})

const { interviewTypeConfig } = useJobStatus()

const emit = defineEmits(['close', 'updated'])
const toast = useToast()

const generating = ref(false)

const renderedMarkdown = computed(() => {
    return props.interview?.ai_prep ? marked(props.interview.ai_prep) : ''
})

const formatDateTime = (dateString) => {
    if (!dateString) return ''
    const d = new Date(dateString)
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const regenerate = async () => {
    if (!props.interview) return
    
    generating.value = true
    try {
        const response = await axios.post(route('jobs.interviews.prep', props.interview.id))
        
        // If interview object is passed directly, we modify it locally to update the view
        if (props.interview) {
            props.interview.ai_prep = response.data.prep
        }
        
        emit('updated', response.data.prep)
        toast.success('Interview prep regenerated!')
    } catch (e) {
        toast.error('Failed to regenerate prep guide')
        console.error(e)
    } finally {
        generating.value = false
    }
}

const closeModal = () => {
    emit('close')
}
</script>
