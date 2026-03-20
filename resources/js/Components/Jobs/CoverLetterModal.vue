<template>
    <DialogModal :show="show" @close="closeModal" maxWidth="3xl">
        <template #title>
            <div class="flex items-center gap-2">
                <span class="text-xl">✍️</span>
                <span>Cover Letter — {{ job?.company_name }}</span>
            </div>
        </template>

        <template #content>
            <div class="space-y-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Paste the job description below, and AI will generate a tailored cover letter for you based on your habit history and skills.
                </p>

                <div v-show="!coverLetter">
                    <div class="relative">
                        <textarea 
                            v-model="jobDescription" 
                            rows="8" 
                            placeholder="Paste job description here..."
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                            :disabled="generating || fetching"
                        ></textarea>
                        
                        <div v-if="fetching" class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 flex flex-col items-center justify-center rounded-md backdrop-blur-[1px]">
                            <RefreshCwIcon class="w-8 h-8 text-indigo-500 animate-spin mb-2" />
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200">Fetching description from URL...</span>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-between items-center">
                        <div>
                            <button v-if="job?.job_url" 
                                @click="fetchDescription" 
                                :disabled="fetching || generating"
                                class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                <RefreshCwIcon class="w-3 h-3" :class="{ 'animate-spin': fetching }" />
                                Re-fetch from URL
                            </button>
                        </div>
                        <PrimaryButton @click="generate" :disabled="!jobDescription.trim() || generating || fetching">
                            <span v-if="generating" class="flex items-center gap-2">
                                <span class="animate-pulse">✨</span> Generating...
                            </span>
                            <span v-else class="flex items-center gap-2">
                                ✨ Generate Cover Letter
                            </span>
                        </PrimaryButton>
                    </div>
                </div>

                <div v-if="coverLetter" class="mt-4">
                    <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-6 max-h-[60vh] overflow-y-auto">
                        <div class="prose dark:prose-invert max-w-none text-sm" v-html="renderedMarkdown"></div>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <button @click="copyToClipboard" class="btn-outline flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors text-sm font-medium">
                            <span v-if="copied" class="text-green-600 dark:text-green-400 flex items-center gap-1">✓ Copied!</span>
                            <span v-else class="flex items-center gap-1">📋 Copy to clipboard</span>
                        </button>
                        
                        <button @click="generate" :disabled="generating" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium flex items-center gap-1 transition-colors">
                            <RefreshCwIcon class="w-4 h-4" :class="{ 'animate-spin': generating }" />
                            Regenerate
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="closeModal">Close</SecondaryButton>
        </template>
    </DialogModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import DialogModal from '@/Components/DialogModal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { useToast } from '@/composables/useToast'
import { RefreshCwIcon } from 'lucide-vue-next'
import { marked } from 'marked'

const props = defineProps({
    show: Boolean,
    job: Object,
})

const emit = defineEmits(['close'])
const toast = useToast()

const jobDescription = ref('')
const coverLetter = ref('')
const generating = ref(false)
const copied = ref(false)

watch(() => props.show, (newVal) => {
    if (newVal) {
        jobDescription.value = props.job?.notes || ''
        coverLetter.value = ''
        copied.value = false
        
        // Auto-fetch if we have a URL and no content in notes
        if (props.job?.job_url && !props.job?.notes) {
            fetchDescription()
        }
    }
})

const renderedMarkdown = computed(() => {
    return coverLetter.value ? marked(coverLetter.value) : ''
})

const fetching = ref(false)

const fetchDescription = async () => {
    if (!props.job?.job_url) return
    
    fetching.value = true
    try {
        const response = await axios.get(route('jobs.fetch-description', props.job.id))
        jobDescription.value = response.data.description
        toast.success('Job description fetched! ✨')
        
        // Auto-generate after successful fetch
        if (jobDescription.value.trim()) {
            generate()
        }
    } catch (error) {
        toast.error('Could not fetch description from URL')
        console.error(error)
    } finally {
        fetching.value = false
    }
}

const generate = async () => {
    if (!jobDescription.value.trim() || !props.job) return

    generating.value = true
    try {
        const response = await axios.post(route('jobs.cover-letter', props.job.id), {
            job_description: jobDescription.value
        })
        coverLetter.value = response.data.cover_letter
    } catch (error) {
        const message = error.response?.data?.error || 'Failed to generate cover letter'
        toast.error(message)
        console.error(error)
    } finally {
        generating.value = false
    }
}

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(coverLetter.value)
        copied.value = true
        setTimeout(() => copied.value = false, 2000)
    } catch (e) {
        toast.error('Failed to copy text')
    }
}

const closeModal = () => {
    emit('close')
}
</script>
