<template>
    <AppLayout title="Templates" subtitle="Pick a pre-built habit to get started quickly">

        <template #header-actions>
            <Link :href="route('habits.create')">
                <Button variant="outline" class="gap-2 dark:border-gray-700 dark:text-gray-300">
                    <Plus class="w-4 h-4" />
                    Custom Habit
                </Button>
            </Link>
        </template>

        <!-- Category tabs -->
        <div class="flex gap-2 flex-wrap mb-6">
            <button @click="activeCategory = null"
                    :class="[
                    'px-4 py-2 rounded-xl text-sm font-medium transition-all border',
                    activeCategory === null
                        ? 'bg-indigo-600 text-white border-indigo-600'
                        : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-indigo-300'
                ]">
                All
            </button>
            <button v-for="category in categories" :key="category"
                    @click="activeCategory = category"
                    :class="[
                    'px-4 py-2 rounded-xl text-sm font-medium transition-all border',
                    activeCategory === category
                        ? 'bg-indigo-600 text-white border-indigo-600'
                        : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-indigo-300'
                ]">
                {{ categoryIcon(category) }} {{ category }}
            </button>
        </div>

        <!-- Template Groups -->
        <div v-for="(templates, category) in filteredGroups" :key="category" class="mb-8">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                {{ categoryIcon(category) }} {{ category }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div v-for="template in templates" :key="template.id"
                     class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-800 transition-all group cursor-pointer"
                     @click="selectTemplate(template)">

                    <div class="p-5">
                        <!-- Icon + Name -->
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-950 rounded-xl flex items-center justify-center text-xl shrink-0">
                                {{ template.icon }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                    {{ template.name }}
                                </h3>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 line-clamp-2">
                                    {{ template.description }}
                                </p>
                            </div>
                        </div>

                        <!-- Meta -->
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                                <Target class="w-3.5 h-3.5" />
                                {{ template.goal }} {{ template.goal_unit }}
                            </span>
                            <span class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                                <RefreshCw class="w-3.5 h-3.5" />
                                {{ template.repeat_count }}x/day
                            </span>
                            <span :class="priorityClass(template.priority)"
                                  class="text-xs px-2 py-0.5 rounded-full font-medium">
                                {{ priorityLabel(template.priority) }}
                            </span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-3 border-t border-gray-50 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            {{ template.deadline_value }} {{ template.deadline_unit }} duration
                        </span>
                        <span class="text-xs text-indigo-500 font-medium opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                            Use template <ArrowRight class="w-3 h-3" />
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Plus, Target, RefreshCw, ArrowRight } from 'lucide-vue-next'

const props = defineProps({
    templateGroups: Object,
})

const activeCategory = ref(null)

const categories = computed(() => Object.keys(props.templateGroups ?? {}))

const filteredGroups = computed(() => {
    if (!activeCategory.value) return props.templateGroups
    return { [activeCategory.value]: props.templateGroups[activeCategory.value] }
})

const categoryIcon = (category) => ({
    'Health & Fitness': '🏃',
    'Education':        '📚',
    'Finance':          '💰',
    'Mental Health':    '🧘',
    'Personal Life':    '🌱',
}[category] ?? '⭐')

const priorityClass = (p) => ({
    1: 'bg-red-100 dark:bg-red-950 text-red-600',
    2: 'bg-yellow-100 dark:bg-yellow-950 text-yellow-600',
    3: 'bg-green-100 dark:bg-green-950 text-green-600',
}[p] ?? '')

const priorityLabel = (p) => ({ 1: 'High', 2: 'Medium', 3: 'Low' }[p] ?? '')

const selectTemplate = (template) => {
    router.visit(route('habits.create', { template: template.id }))
}
</script>
