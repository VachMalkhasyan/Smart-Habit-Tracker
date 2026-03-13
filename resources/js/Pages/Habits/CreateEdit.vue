<template>
    <AppLayout :title="isEditing ? 'Edit Habit' : 'Create Habit'"
               :subtitle="isEditing ? 'Update your habit details' : 'Build a new habit to track'">

        <div class="max-w-2xl mx-auto">
            <div v-if="template"
                 class="bg-indigo-50 dark:bg-indigo-950 border border-indigo-200 dark:border-indigo-800 rounded-2xl p-4 flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-white dark:bg-indigo-900 rounded-xl flex items-center justify-center text-xl shrink-0">
                    {{ template.icon }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                        Using template: {{ template.name }}
                    </p>
                    <p class="text-xs text-indigo-500 dark:text-indigo-400">
                        Fields pre-filled — customize as needed
                    </p>
                </div>
            </div>
            <div v-if="!template" class="flex justify-center mb-2">
                <button type="button" @click="templateModal = true"
                        class="flex items-center gap-2 text-sm text-indigo-500 hover:text-indigo-600 border border-dashed border-indigo-300 dark:border-indigo-700 px-4 py-2 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-950 transition-all">
                    <LayoutTemplate class="w-4 h-4" />
                    Start from a template
                </button>
            </div>
            <Dialog v-model:open="templateModal">
                <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto dark:bg-gray-900">
                    <DialogHeader>
                        <DialogTitle class="dark:text-gray-100">Choose a Template</DialogTitle>
                        <DialogDescription class="dark:text-gray-400">
                            Pick a template to pre-fill the form
                        </DialogDescription>
                    </DialogHeader>

                    <!-- Category filter -->
                    <div class="flex gap-2 flex-wrap my-3">
                        <button @click="modalCategory = null"
                                :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition-all',
                    modalCategory === null ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400']">
                            All
                        </button>
                        <button v-for="cat in templateCategories" :key="cat"
                                @click="modalCategory = cat"
                                :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition-all',
                    modalCategory === cat ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400']">
                            {{ cat }}
                        </button>
                    </div>

                    <!-- Templates grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button v-for="t in filteredModalTemplates" :key="t.id" type="button"
                                @click="applyTemplate(t)"
                                class="flex items-start gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-800 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-950 transition-all text-left">
                            <span class="text-2xl">{{ t.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ t.name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 line-clamp-1">{{ t.description }}</p>
                                <p class="text-xs text-indigo-500 mt-1">{{ t.goal }} {{ t.goal_unit }} · {{ t.repeat_count }}x/day</p>
                            </div>
                        </button>
                    </div>
                </DialogContent>
            </Dialog>
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Basic Info Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-5">Basic Information</h2>

                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Habit Name <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.name" type="text"
                                   placeholder="e.g. Read 30 minutes"
                                   class="w-full px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-900 dark:text-gray-100"
                                   :class="errors.name ? 'border-red-400 dark:border-red-600' : 'border-gray-200 dark:border-gray-700'" />
                            <p v-if="errors.name" class="text-xs text-red-400 mt-1">{{ errors.name }}</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Description <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <textarea v-model="form.description"
                                      placeholder="What is this habit about?"
                                      rows="3"
                                      class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none">
                            </textarea>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">Category</label>
                            <div class="flex gap-2 flex-wrap">
                                <button v-for="cat in categories" :key="cat.id" type="button"
                                        @click="form.category_id = cat.id"
                                        :class="[
                                        'px-3 py-1.5 rounded-xl text-sm border transition-all',
                                        form.category_id === cat.id
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600'
                                    ]">
                                    {{ cat.name }}
                                </button>
                            </div>
                            <p v-if="errors.category_id" class="text-xs text-red-400 mt-1">{{ errors.category_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Goal & Schedule Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-5">Goal & Schedule</h2>

                    <div class="space-y-4">

                        <!-- Goal -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Goal <span class="text-red-400">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input v-model="form.goal" type="number" min="1"
                                       placeholder="30"
                                       class="w-28 px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-900 dark:text-gray-100"
                                       :class="errors.goal ? 'border-red-400 dark:border-red-600' : 'border-gray-200 dark:border-gray-700'" />
                                <select v-model="form.goal_unit"
                                        class="flex-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600">
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                    <option value="years">Years</option>
                                </select>
                            </div>
                            <p v-if="errors.goal" class="text-xs text-red-400 mt-1">{{ errors.goal }}</p>
                        </div>

                        <!-- Repeat Count -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Times per day <span class="text-red-400">*</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="decrement"
                                        class="w-9 h-9 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    <Minus class="w-4 h-4" />
                                </button>
                                <span class="text-lg font-semibold text-gray-800 dark:text-gray-100 w-8 text-center">
                                    {{ form.repeat_count }}
                                </span>
                                <button type="button" @click="increment"
                                        class="w-9 h-9 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    <Plus class="w-4 h-4" />
                                </button>
                                <span class="text-sm text-gray-400 dark:text-gray-500">times per day</span>
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Start Date <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.start_date" type="date"
                                   class="w-full px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-900 dark:text-gray-100"
                                   :class="errors.start_date ? 'border-red-400 dark:border-red-600' : 'border-gray-200 dark:border-gray-700'" />
                            <p v-if="errors.start_date" class="text-xs text-red-400 mt-1">{{ errors.start_date }}</p>
                        </div>

                        <!-- Deadline -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Duration <span class="text-red-400">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input v-model="form.deadline_value" type="number" min="1"
                                       placeholder="2"
                                       class="w-28 px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-900 dark:text-gray-100"
                                       :class="errors.deadline_value ? 'border-red-400 dark:border-red-600' : 'border-gray-200 dark:border-gray-700'" />
                                <select v-model="form.deadline_unit"
                                        class="flex-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600">
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                    <option value="years">Years</option>
                                </select>
                            </div>
                            <p v-if="errors.deadline_value" class="text-xs text-red-400 mt-1">{{ errors.deadline_value }}</p>
                        </div>

                        <!-- Computed end date hint -->
                        <p v-if="endDate" class="text-xs text-indigo-500 bg-indigo-50 dark:bg-indigo-950 px-3 py-2 rounded-lg">
                            📅 This habit will end on <strong>{{ endDate }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Priority & Status Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-5">Priority & Status</h2>

                    <div class="space-y-4">
                        <!-- Priority -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 block">Priority</label>
                            <div class="flex gap-3">
                                <button v-for="p in priorities" :key="p.value" type="button"
                                        @click="form.priority = p.value"
                                        :class="[
                                        'flex-1 py-2.5 rounded-xl border text-sm font-medium transition-all',
                                        form.priority === p.value ? p.activeClass : 'border-gray-200 text-gray-500 hover:border-gray-300 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600'
                                    ]">
                                    {{ p.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Status (only on edit) -->
                        <div v-if="isEditing">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 block">Status</label>
                            <div class="flex gap-2 flex-wrap">
                                <button v-for="s in statuses" :key="s.value" type="button"
                                        @click="form.status = s.value"
                                        :class="[
                                        'px-4 py-2 rounded-xl border text-sm font-medium transition-all capitalize',
                                        form.status === s.value ? s.activeClass : 'border-gray-200 text-gray-500 hover:border-gray-300 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600'
                                    ]">
                                    {{ s.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pb-6">
                    <Link :href="route('habits.index')">
                        <Button type="button" variant="outline" class="gap-2">
                            <ArrowLeft class="w-4 h-4" /> Cancel
                        </Button>
                    </Link>
                    <Button type="submit"
                            :disabled="form.processing"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 px-8">
                        <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                        <Check v-else class="w-4 h-4" />
                        {{ isEditing ? 'Save Changes' : 'Create Habit' }}
                    </Button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import {computed, ref, watch} from 'vue'
import {Link, router, useForm} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Plus, Minus, ArrowLeft, Check, Loader2 } from 'lucide-vue-next'
import dayjs from 'dayjs'

import { LayoutTemplate } from 'lucide-vue-next'

const templateModal    = ref(false)
const modalCategory    = ref(null)


const applyTemplate = (t) => {
    form.name           = t.name
    form.description    = t.description ?? ''
    form.goal           = t.goal
    form.goal_unit      = t.goal_unit
    form.repeat_count   = t.repeat_count
    form.deadline_value = t.deadline_value
    form.deadline_unit  = t.deadline_unit
    form.priority       = t.priority
    templateModal.value = false
}

const props = defineProps({
    habit: Object,
    categories: Array,
    errors: Object,
    template:   Object,
    templateGroups: Object,   // ← add this

})

const allTemplates = computed(() => {
    return Object.values(props.templateGroups ?? {}).flat()
})

const templateCategories = computed(() =>
    Object.keys(props.templateGroups ?? {})
)

const filteredModalTemplates = computed(() => {
    if (!modalCategory.value) return allTemplates.value
    return allTemplates.value.filter(t => t.category === modalCategory.value)
})

const isEditing = computed(() => !!props.habit)

const form = useForm({
    name:           props.habit?.name           ?? props.template?.name           ?? '',
    description:    props.habit?.description    ?? props.template?.description    ?? '',
    category_id:    props.habit?.category_id    ?? null,
    goal:           props.habit?.goal           ?? props.template?.goal           ?? 30,
    goal_unit:      props.habit?.goal_unit      ?? props.template?.goal_unit      ?? 'days',
    repeat_count:   props.habit?.repeat_count   ?? props.template?.repeat_count   ?? 1,
    start_date:     props.habit?.start_date     ?? new Date().toISOString().split('T')[0],
    deadline_value: props.habit?.deadline_value ?? props.template?.deadline_value ?? 30,
    deadline_unit:  props.habit?.deadline_unit  ?? props.template?.deadline_unit  ?? 'days',
    priority:       props.habit?.priority       ?? props.template?.priority       ?? 2,
    status:         props.habit?.status         ?? 'active',
})

// Computed end date
const endDate = computed(() => {
    if (!form.start_date || !form.deadline_value || !form.deadline_unit) return null
    return dayjs(form.start_date)
        .add(form.deadline_value, form.deadline_unit)
        .format('MMM D, YYYY')
})

// Repeat count
const increment = () => form.repeat_count < 20 && form.repeat_count++
const decrement = () => form.repeat_count > 1 && form.repeat_count--

// Priority options
const priorities = [
    { value: 1, label: '🔴 High',   activeClass: 'border-red-400 bg-red-50 text-red-600 dark:border-red-500/50 dark:bg-red-900/30 dark:text-red-400' },
    { value: 2, label: '🟡 Medium', activeClass: 'border-yellow-400 bg-yellow-50 text-yellow-600 dark:border-yellow-500/50 dark:bg-yellow-900/30 dark:text-yellow-400' },
    { value: 3, label: '🟢 Low',    activeClass: 'border-green-400 bg-green-50 text-green-600 dark:border-green-500/50 dark:bg-green-900/30 dark:text-green-400' },
]

// Status options
const statuses = [
    { value: 'active',    label: 'Active',    activeClass: 'border-green-400 bg-green-50 text-green-700 dark:border-green-500/50 dark:bg-green-900/30 dark:text-green-400' },
    { value: 'paused',    label: 'Paused',    activeClass: 'border-yellow-400 bg-yellow-50 text-yellow-700 dark:border-yellow-500/50 dark:bg-yellow-900/30 dark:text-yellow-400' },
    { value: 'inactive',  label: 'Inactive',  activeClass: 'border-gray-400 bg-gray-50 text-gray-700 dark:border-gray-500/50 dark:bg-gray-800/50 dark:text-gray-400' },
    { value: 'completed', label: 'Completed', activeClass: 'border-blue-400 bg-blue-50 text-blue-700 dark:border-blue-500/50 dark:bg-blue-900/30 dark:text-blue-400' },
]

// Submit
const submit = () => {
    if (isEditing.value) {
        form.put(route('habits.update', props.habit.id))
    } else {
        form.post(route('habits.store'))
    }
}
</script>
