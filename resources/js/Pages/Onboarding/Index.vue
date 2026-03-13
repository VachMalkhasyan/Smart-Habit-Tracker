<template>
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 flex items-center justify-center p-4">

        <!-- Skip button -->
        <button @click="skip"
                class="fixed top-6 right-6 text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors z-50">
            Skip setup →
        </button>

        <!-- Drawer Card -->
        <div class="w-full max-w-lg">

            <!-- Progress dots -->
            <div class="flex items-center justify-center gap-2 mb-8">
                <div v-for="i in totalSteps" :key="i"
                     :class="[
                        'rounded-full transition-all duration-300',
                        i === currentStep
                            ? 'w-8 h-2.5 bg-indigo-600'
                            : i < currentStep
                                ? 'w-2.5 h-2.5 bg-indigo-400'
                                : 'w-2.5 h-2.5 bg-gray-200 dark:bg-gray-700'
                    ]">
                </div>
            </div>

            <!-- Step card -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-800 overflow-hidden">

                <!-- Step 1: Welcome -->
                <transition name="slide" mode="out-in">
                    <div v-if="currentStep === 1" key="step1" class="p-8">
                        <div class="text-center mb-8">
                            <div class="w-20 h-20 bg-indigo-50 dark:bg-indigo-950 rounded-2xl flex items-center justify-center mx-auto mb-5 text-4xl">
                                👋
                            </div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                Welcome, {{ userName }}!
                            </h1>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                Let's personalize your experience. It only takes a minute.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Username <span class="text-gray-400 text-xs">(optional)</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">@</span>
                                    <input v-model="form.username" type="text"
                                           placeholder="e.g. john_doe"
                                           class="w-full pl-8 pr-4 py-3 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                                </div>
                                <p v-if="errors.username" class="text-red-500 text-xs mt-1">{{ errors.username }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Bio <span class="text-gray-400 text-xs">(optional)</span>
                                </label>
                                <input v-model="form.bio" type="text"
                                       placeholder="Tell us a bit about yourself..."
                                       class="w-full px-4 py-3 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Pick interests -->
                    <div v-else-if="currentStep === 2" key="step2" class="p-8">
                        <div class="text-center mb-8">
                            <div class="w-20 h-20 bg-purple-50 dark:bg-purple-950 rounded-2xl flex items-center justify-center mx-auto mb-5 text-4xl">
                                🎯
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                What are you working on?
                            </h2>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                Pick categories that interest you. We'll suggest relevant habits.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <button v-for="cat in categories" :key="cat.id"
                                    @click="toggleInterest(cat.id)"
                                    :class="[
                                'flex items-center gap-3 p-3.5 rounded-xl border-2 transition-all text-left',
                                form.interests.includes(cat.id)
                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950'
                                    : 'border-gray-100 dark:border-gray-800 hover:border-indigo-200 dark:hover:border-indigo-800'
                            ]">
                                <span class="text-2xl">{{ categoryIcon(cat.name) }}</span>
                                <span :class="[
                                'text-sm font-medium',
                                form.interests.includes(cat.id)
                                    ? 'text-indigo-700 dark:text-indigo-300'
                                    : 'text-gray-700 dark:text-gray-300'
                            ]">{{ cat.name }}</span>
                                <span v-if="form.interests.includes(cat.id)"
                                      class="ml-auto text-indigo-500">
                                <Check class="w-4 h-4" />
                            </span>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Create first habit -->
                    <div v-else-if="currentStep === 3" key="step3" class="p-8">
                        <div class="text-center mb-8">
                            <div class="w-20 h-20 bg-green-50 dark:bg-green-950 rounded-2xl flex items-center justify-center mx-auto mb-5 text-4xl">
                                ✨
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                Create your first habit
                            </h2>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                Start small. One habit can change everything.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Habit name
                                </label>
                                <input v-model="form.habit_name" type="text"
                                       placeholder="e.g. Drink 8 glasses of water"
                                       class="w-full px-4 py-3 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Category
                                </label>
                                <select v-model="form.habit_category"
                                        class="w-full px-4 py-3 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                    <option value="">No category</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ categoryIcon(cat.name) }} {{ cat.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Goal
                                    </label>
                                    <input v-model="form.habit_goal" type="number" min="1"
                                           class="w-full px-4 py-3 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Unit
                                    </label>
                                    <select v-model="form.habit_goal_unit"
                                            class="w-full px-4 py-3 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                        <option value="days">Days</option>
                                        <option value="weeks">Weeks</option>
                                        <option value="months">Months</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Quick templates -->
                            <div>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Quick picks:</p>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="t in quickTemplates" :key="t.name"
                                            @click="applyQuickTemplate(t)"
                                            class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-950 text-gray-600 dark:text-gray-400 transition-all">
                                        {{ t.icon }} {{ t.name }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Reminder time -->
                    <div v-else-if="currentStep === 4" key="step4" class="p-8">
                        <div class="text-center mb-8">
                            <div class="w-20 h-20 bg-orange-50 dark:bg-orange-950 rounded-2xl flex items-center justify-center mx-auto mb-5 text-4xl">
                                ⏰
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                Set your reminder
                            </h2>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">
                                We'll send you a daily email to keep you on track.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <!-- Time presets -->
                            <div class="grid grid-cols-3 gap-3">
                                <button v-for="preset in timePresets" :key="preset.time"
                                        @click="form.reminder_time = preset.time"
                                        :class="[
                                    'flex flex-col items-center p-4 rounded-xl border-2 transition-all',
                                    form.reminder_time === preset.time
                                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950'
                                        : 'border-gray-100 dark:border-gray-800 hover:border-indigo-200'
                                ]">
                                    <span class="text-2xl mb-1.5">{{ preset.icon }}</span>
                                    <span :class="[
                                    'text-xs font-medium',
                                    form.reminder_time === preset.time
                                        ? 'text-indigo-600 dark:text-indigo-300'
                                        : 'text-gray-600 dark:text-gray-400'
                                ]">{{ preset.label }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ preset.time }}
                                </span>
                                </button>
                            </div>

                            <!-- Custom time -->
                            <div class="flex items-center gap-3 pt-2">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Custom time:</span>
                                <input v-model="form.reminder_time" type="time"
                                       class="text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                            </div>

                            <!-- Summary card -->
                            <div class="bg-indigo-50 dark:bg-indigo-950 rounded-xl p-4 mt-2">
                                <p class="text-sm text-indigo-700 dark:text-indigo-300 font-medium">
                                    🎉 You're all set!
                                </p>
                                <p class="text-xs text-indigo-500 dark:text-indigo-400 mt-1">
                                    Daily reminder at <strong>{{ form.reminder_time }}</strong>
                                    <span v-if="form.habit_name"> · First habit: <strong>{{ form.habit_name }}</strong></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Footer nav -->
                <div class="px-8 py-5 border-t border-gray-50 dark:border-gray-800 flex items-center justify-between">
                    <button v-if="currentStep > 1"
                            @click="currentStep--"
                            class="flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <ChevronLeft class="w-4 h-4" /> Back
                    </button>
                    <div v-else></div>

                    <button
                        v-if="currentStep < totalSteps"
                        @click="nextStep"
                        class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                        Continue <ChevronRight class="w-4 h-4" />
                    </button>

                    <button
                        v-else
                        @click="complete"
                        :disabled="completing"
                        class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-colors disabled:opacity-60">
                        <span v-if="completing">Setting up...</span>
                        <span v-else class="flex items-center gap-2">
                            Get started <Rocket class="w-4 h-4" />
                        </span>
                    </button>
                </div>
            </div>

            <!-- Step counter -->
            <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-4">
                Step {{ currentStep }} of {{ totalSteps }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Check, ChevronLeft, ChevronRight, Rocket } from 'lucide-vue-next'

const props = defineProps({
    categories: Array,
})

const page       = usePage()
const userName   = computed(() => page.props.auth?.user?.name?.split(' ')[0] ?? 'there')
const totalSteps = 4
const currentStep = ref(1)
const completing  = ref(false)
const errors      = ref({})

const form = ref({
    username:        '',
    bio:             '',
    interests:       [],
    habit_name:      '',
    habit_category:  '',
    habit_goal:      30,
    habit_goal_unit: 'days',
    reminder_time:   '08:00',
})

// Step 2 — interests
const toggleInterest = (id) => {
    const idx = form.value.interests.indexOf(id)
    if (idx === -1) form.value.interests.push(id)
    else form.value.interests.splice(idx, 1)
}

const categoryIcon = (name) => ({
    'Health & Fitness': '🏃',
    'Education':        '📚',
    'Finance':          '💰',
    'Mental Health':    '🧘',
    'Personal Life':    '🌱',
    'Social':           '👥',
}[name] ?? '⭐')

// Step 3 — quick templates
const quickTemplates = [
    { icon: '💧', name: 'Drink water',    category: null, goal: 30 },
    { icon: '🏃', name: 'Exercise',       category: null, goal: 30 },
    { icon: '📚', name: 'Read daily',     category: null, goal: 30 },
    { icon: '🧘', name: 'Meditate',       category: null, goal: 30 },
    { icon: '😴', name: 'Sleep 8 hours',  category: null, goal: 30 },
    { icon: '✍️', name: 'Journal',        category: null, goal: 30 },
]

const applyQuickTemplate = (t) => {
    form.value.habit_name = t.name
    form.value.habit_goal = t.goal
}

// Step 4 — time presets
const timePresets = [
    { icon: '🌅', label: 'Morning',   time: '07:00' },
    { icon: '☀️', label: 'Mid-day',   time: '12:00' },
    { icon: '🌙', label: 'Evening',   time: '20:00' },
]

// Navigation
const nextStep = () => {
    errors.value = {}
    currentStep.value++
}

const complete = () => {
    completing.value = true
    router.post(route('onboarding.complete'), form.value, {
        onError:  (e) => { errors.value = e; completing.value = false },
        onFinish: () => { completing.value = false }
    })
}

const skip = () => {
    router.post(route('onboarding.skip'))
}
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: all 0.25s ease;
}
.slide-enter-from {
    opacity: 0;
    transform: translateX(30px);
}
.slide-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}
</style>
