<template>
    <DialogModal :show="show" @close="closeModal" maxWidth="xl">
        <template #title>
            {{ job ? 'Edit Job Application' : 'Add New Application' }}
        </template>

        <template #content>
            <form @submit.prevent="submit" class="space-y-4">
                <!-- Basic Info -->
                <div class="space-y-4">
                    <div>
                        <InputLabel for="company_name" value="Company Name *" />
                        <TextInput ref="companyInput" id="company_name" v-model="form.company_name" type="text" class="mt-1 block w-full" required autofocus />
                        <InputError :message="form.errors.company_name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="role_title" value="Role Title *" />
                        <TextInput id="role_title" v-model="form.role_title" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.role_title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="job_url" value="Job Posting URL" />
                        <div class="relative mt-1 border-gray-300 dark:border-gray-700 focus-within:border-indigo-500 rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <LinkIcon class="h-4 w-4 text-gray-400" />
                            </div>
                            <TextInput id="job_url" v-model="form.job_url" type="url" class="pl-10 block w-full" placeholder="https://..." />
                        </div>
                        <InputError :message="form.errors.job_url" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Status *" class="mb-2" />
                        <div class="flex flex-wrap gap-2">
                            <label v-for="(cfg, key) in statusConfig" :key="key"
                                class="cursor-pointer">
                                <input type="radio" v-model="form.status" :value="key" class="sr-only" />
                                <span class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors shadow-sm"
                                      :class="form.status === key ? cfg.badge + ' ' + cfg.border : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'">
                                    {{ cfg.label }}
                                </span>
                            </label>
                        </div>
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Priority *" class="mb-2" />
                        <div class="grid grid-cols-3 gap-3">
                            <label v-for="(cfg, key) in priorityConfig" :key="key" class="cursor-pointer">
                                <input type="radio" v-model="form.priority" :value="Number(key)" class="sr-only" />
                                <div class="px-3 py-2 border rounded-lg text-center transition-all shadow-sm"
                                     :class="form.priority === Number(key) ? cfg.badge + ' border-current' : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800'">
                                    {{ cfg.dot }} {{ cfg.label }}
                                </div>
                            </label>
                        </div>
                        <InputError :message="form.errors.priority" class="mt-2" />
                    </div>
                </div>

                <!-- Toggle Details -->
                <div class="pt-2">
                    <button type="button" @click="showDetails = !showDetails" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 text-sm font-medium flex items-center gap-1">
                        {{ showDetails ? 'Hide details -' : 'Add details +' }}
                    </button>
                </div>

                <!-- Extended Info -->
                <div v-show="showDetails" class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700 mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="location" value="Location" />
                            <TextInput id="location" v-model="form.location" type="text" class="mt-1 block w-full" />
                            <InputError :message="form.errors.location" class="mt-2" />
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="flex items-center">
                                <Checkbox v-model:checked="form.is_remote" />
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remote</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <InputLabel for="salary_min" value="Min Salary" />
                            <TextInput id="salary_min" v-model="form.salary_min" type="number" class="mt-1 block w-full" />
                            <InputError :message="form.errors.salary_min" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="salary_max" value="Max Salary" />
                            <TextInput id="salary_max" v-model="form.salary_max" type="number" class="mt-1 block w-full" />
                            <InputError :message="form.errors.salary_max" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="currency" value="Currency" />
                            <select id="currency" v-model="form.currency" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="GBP">GBP (£)</option>
                                <option value="CAD">CAD ($)</option>
                                <option value="AUD">AUD ($)</option>
                            </select>
                            <InputError :message="form.errors.currency" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="applied_date" value="Applied Date" />
                        <TextInput id="applied_date" v-model="form.applied_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.applied_date" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="notes" value="Notes" />
                        <textarea id="notes" v-model="form.notes" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                        <InputError :message="form.errors.notes" class="mt-2" />
                    </div>
                </div>
            </form>
        </template>

        <template #footer>
            <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
            <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="submit">
                {{ job ? 'Save Changes' : 'Add Application' }}
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import Checkbox from '@/Components/Checkbox.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { Link as LinkIcon } from 'lucide-vue-next'
import { useJobStatus } from '@/composables/useJobStatus'

const props = defineProps({
    show: Boolean,
    job: Object,
    statuses: Object,
})

const { statusConfig, priorityConfig } = useJobStatus()

const emit = defineEmits(['close', 'saved'])

const showDetails = ref(false)
const companyInput = ref(null)

const form = useForm({
    company_name: '',
    role_title: '',
    job_url: '',
    status: 'wishlist',
    priority: 2,
    salary_min: '',
    salary_max: '',
    currency: 'USD',
    location: '',
    is_remote: false,
    applied_date: '',
    notes: '',
})

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.job) {
            form.company_name = props.job.company_name
            form.role_title   = props.job.role_title
            form.job_url      = props.job.job_url || ''
            form.status       = props.job.status
            form.priority     = props.job.priority
            form.salary_min   = props.job.salary_min || ''
            form.salary_max   = props.job.salary_max || ''
            form.currency     = props.job.currency || 'USD'
            form.location     = props.job.location || ''
            form.is_remote    = props.job.is_remote
            form.applied_date = props.job.applied_date ? props.job.applied_date.split('T')[0] : ''
            form.notes        = props.job.notes || ''
            showDetails.value = !!(props.job.location || props.job.salary_min || props.job.notes)
        } else {
            form.reset()
            showDetails.value = false
            form.applied_date = new Date().toISOString().split('T')[0]
        }
        setTimeout(() => companyInput.value?.focus(), 100)
    }
})

// Auto-set applied date if status changes to applied
watch(() => form.status, (newStatus) => {
    if (newStatus === 'applied' && !form.applied_date && !props.job) {
        form.applied_date = new Date().toISOString().split('T')[0]
    }
})

const submit = () => {
    if (props.job) {
        form.patch(route('jobs.update', props.job.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset()
                emit('saved')
            }
        })
    } else {
        form.post(route('jobs.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset()
                emit('saved')
            }
        })
    }
}

const closeModal = () => {
    emit('close')
    form.clearErrors()
}
</script>
