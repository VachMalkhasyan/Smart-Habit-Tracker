<template>
    <DialogModal :show="show" @close="closeModal" maxWidth="3xl">
        <template #title>
            <div class="flex items-center gap-2">
                <span class="text-xl">🔍</span>
                <span>Company Research — {{ job?.company_name }}</span>
            </div>
        </template>

        <template #content>
            <div class="space-y-4 min-h-[40vh]">
                <div v-if="loading" class="flex flex-col items-center justify-center py-12 text-gray-400">
                    <div class="animate-pulse flex items-center gap-3 text-lg font-medium text-indigo-600 mb-6">
                        <span class="animate-spin text-2xl">⚡</span> Researching {{ job?.company_name }}...
                    </div>
                    
                    <div class="w-full max-w-2xl space-y-4 px-6">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2 mt-8"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-4/5"></div>
                    </div>
                </div>

                <div v-else-if="researchData" class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-6 max-h-[65vh] overflow-y-auto">
                    <div class="prose dark:prose-invert max-w-none text-sm" v-html="renderedMarkdown"></div>
                </div>
                
                <div v-else class="text-center py-12 text-red-500">
                    Failed to load research data. Please try again.
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
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { marked } from 'marked'

const props = defineProps({
    show: Boolean,
    job: Object,
})

const emit = defineEmits(['close'])

const researchData = ref('')
const loading = ref(false)

watch(() => props.show, (newVal) => {
    if (newVal && props.job) {
        fetchResearch()
    }
})

const renderedMarkdown = computed(() => {
    return researchData.value ? marked(researchData.value) : ''
})

const fetchResearch = async () => {
    loading.value = true
    researchData.value = ''
    try {
        const response = await axios.post(route('jobs.research', props.job.id))
        researchData.value = response.data.research
    } catch (error) {
        console.error('Failed to fetch research', error)
        researchData.value = null
    } finally {
        loading.value = false
    }
}

const closeModal = () => {
    emit('close')
}
</script>
