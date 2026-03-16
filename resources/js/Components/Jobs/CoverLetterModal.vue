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
                    <textarea 
                        v-model="jobDescription" 
                        rows="8" 
                        placeholder="Paste job description here..."
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                        :disabled="generating"
                    ></textarea>

                    <div class="mt-4 flex justify-end">
                        <PrimaryButton @click="generate" :disabled="!jobDescription.trim() || generating">
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
        jobDescription.value = ''
        coverLetter.value = ''
        copied.value = false
    }
})

const renderedMarkdown = computed(() => {
    return coverLetter.value ? marked(coverLetter.value) : ''
})

const generate = async () => {
    if (!jobDescription.value.trim() || !props.job) return

    generating.value = true
    try {
        const response = await axios.post(route('jobs.cover-letter', props.job.id), {
            job_description: jobDescription.value
        })
        coverLetter.value = response.data.cover_letter
    } catch (error) {
        toast.error('Failed to generate cover letter')
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
