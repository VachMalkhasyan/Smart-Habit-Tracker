<template>
    <AppLayout :title="isEditing ? 'Edit Habit' : 'Create Habit'"
               :subtitle="isEditing ? 'Update your habit details' : 'Build a new habit to track'">

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Basic Info Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 mb-5">Basic Information</h2>

                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">
                                Habit Name <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.name" type="text"
                                   placeholder="e.g. Read 30 minutes"
                                   class="w-full px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                   :class="errors.name ? 'border-red-400' : 'border-gray-200'" />
                            <p v-if="errors.name" class="text-xs text-red-400 mt-1">{{ errors.name }}</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">
                                Description <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <textarea v-model="form.description"
                                      placeholder="What is this habit about?"
                                      rows="3"
                                      class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none">
                            </textarea>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Category</label>
                            <div class="flex gap-2 flex-wrap">
                                <button v-for="cat in categories" :key="cat.id" type="button"
                                        @click="form.category_id = cat.id"
                                        :class="[
                                        'px-3 py-1.5 rounded-xl text-sm border transition-all',
                                        form.category_id === cat.id
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300'
                                    ]">
                                    {{ cat.name }}
                                </button>
                            </div>
                            <p v-if="errors.category_id" class="text-xs text-red-400 mt-1">{{ errors.category_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Goal & Schedule Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 mb-5">Goal & Schedule</h2>

                    <div class="space-y-4">

                        <!-- Goal -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">
                                Goal <span class="text-red-400">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input v-model="form.goal" type="number" min="1"
                                       placeholder="30"
                                       class="w-28 px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                       :class="errors.goal ? 'border-red-400' : 'border-gray-200'" />
                                <select v-model="form.goal_unit"
                                        class="flex-1 px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300">
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
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">
                                Times per day <span class="text-red-400">*</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="decrement"
                                        class="w-9 h-9 rounded-xl border border-gray-200 flex items-center justify-center hover:bg-gray-50 text-gray-600">
                                    <Minus class="w-4 h-4" />
                                </button>
                                <span class="text-lg font-semibold text-gray-800 w-8 text-center">
                                    {{ form.repeat_count }}
                                </span>
                                <button type="button" @click="increment"
                                        class="w-9 h-9 rounded-xl border border-gray-200 flex items-center justify-center hover:bg-gray-50 text-gray-600">
                                    <Plus class="w-4 h-4" />
                                </button>
                                <span class="text-sm text-gray-400">times per day</span>
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">
                                Start Date <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.start_date" type="date"
                                   class="w-full px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                   :class="errors.start_date ? 'border-red-400' : 'border-gray-200'" />
                            <p v-if="errors.start_date" class="text-xs text-red-400 mt-1">{{ errors.start_date }}</p>
                        </div>

                        <!-- Deadline -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">
                                Duration <span class="text-red-400">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input v-model="form.deadline_value" type="number" min="1"
                                       placeholder="2"
                                       class="w-28 px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                       :class="errors.deadline_value ? 'border-red-400' : 'border-gray-200'" />
                                <select v-model="form.deadline_unit"
                                        class="flex-1 px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                    <option value="years">Years</option>
                                </select>
                            </div>
                            <p v-if="errors.deadline_value" class="text-xs text-red-400 mt-1">{{ errors.deadline_value }}</p>
                        </div>

                        <!-- Computed end date hint -->
                        <p v-if="endDate" class="text-xs text-indigo-500 bg-indigo-50 px-3 py-2 rounded-lg">
                            📅 This habit will end on <strong>{{ endDate }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Priority & Status Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 mb-5">Priority & Status</h2>

                    <div class="space-y-4">
                        <!-- Priority -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-2 block">Priority</label>
                            <div class="flex gap-3">
                                <button v-for="p in priorities" :key="p.value" type="button"
                                        @click="form.priority = p.value"
                                        :class="[
                                        'flex-1 py-2.5 rounded-xl border text-sm font-medium transition-all',
                                        form.priority === p.value ? p.activeClass : 'border-gray-200 text-gray-500 hover:border-gray-300'
                                    ]">
                                    {{ p.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Status (only on edit) -->
                        <div v-if="isEditing">
                            <label class="text-sm font-medium text-gray-700 mb-2 block">Status</label>
                            <div class="flex gap-2 flex-wrap">
                                <button v-for="s in statuses" :key="s.value" type="button"
                                        @click="form.status = s.value"
                                        :class="[
                                        'px-4 py-2 rounded-xl border text-sm font-medium transition-all capitalize',
                                        form.status === s.value ? s.activeClass : 'border-gray-200 text-gray-500 hover:border-gray-300'
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
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Plus, Minus, ArrowLeft, Check, Loader2 } from 'lucide-vue-next'
import dayjs from 'dayjs'

const props = defineProps({
    habit: Object,       // null on create, filled on edit
    categories: Array,
    errors: Object,
})

const isEditing = computed(() => !!props.habit)

const form = useForm({
    name:           props.habit?.name ?? '',
    description:    props.habit?.description ?? '',
    category_id:    props.habit?.category_id ?? null,
    goal:           props.habit?.goal ?? 30,
    goal_unit:      props.habit?.goal_unit ?? 'days',
    repeat_count:   props.habit?.repeat_count ?? 1,
    start_date:     props.habit?.start_date ?? new Date().toISOString().split('T')[0],
    deadline_value: props.habit?.deadline_value ?? 30,
    deadline_unit:  props.habit?.deadline_unit ?? 'days',
    priority:       props.habit?.priority ?? 2,
    status:         props.habit?.status ?? 'active',
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
    { value: 1, label: '🔴 High',   activeClass: 'border-red-400 bg-red-50 text-red-600' },
    { value: 2, label: '🟡 Medium', activeClass: 'border-yellow-400 bg-yellow-50 text-yellow-600' },
    { value: 3, label: '🟢 Low',    activeClass: 'border-green-400 bg-green-50 text-green-600' },
]

// Status options
const statuses = [
    { value: 'active',    label: 'Active',    activeClass: 'border-green-400 bg-green-50 text-green-700' },
    { value: 'paused',    label: 'Paused',    activeClass: 'border-yellow-400 bg-yellow-50 text-yellow-700' },
    { value: 'inactive',  label: 'Inactive',  activeClass: 'border-gray-400 bg-gray-50 text-gray-700' },
    { value: 'completed', label: 'Completed', activeClass: 'border-blue-400 bg-blue-50 text-blue-700' },
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
