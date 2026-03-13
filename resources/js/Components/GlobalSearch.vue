<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-w-lg p-0 gap-0 overflow-hidden dark:bg-gray-900">

            <!-- Search Input -->
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                <Search class="w-4 h-4 text-gray-400 shrink-0" />
                <input
                    ref="searchInput"
                    v-model="query"
                    type="text"
                    placeholder="Search habits, categories..."
                    class="flex-1 text-sm bg-transparent focus:outline-none text-gray-800 dark:text-gray-100 placeholder-gray-400"
                    @keydown.escape="$emit('update:open', false)"
                    @keydown.arrow-down.prevent="moveDown"
                    @keydown.arrow-up.prevent="moveUp"
                    @keydown.enter.prevent="selectCurrent"
                />
                <kbd class="text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-400 rounded border border-gray-200 dark:border-gray-700">
                    esc
                </kbd>
            </div>

            <!-- Results -->
            <div class="max-h-96 overflow-y-auto">

                <!-- Loading -->
                <div v-if="loading" class="flex items-center justify-center py-8">
                    <Loader2 class="w-5 h-5 animate-spin text-indigo-400" />
                </div>

                <!-- Empty query -->
                <div v-else-if="!query" class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                    Start typing to search...
                </div>

                <!-- No results -->
                <div v-else-if="results.habits.length === 0 && results.categories.length === 0"
                     class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                    No results for "<strong>{{ query }}</strong>"
                </div>

                <!-- Results list -->
                <div v-else>

                    <!-- Habits -->
                    <div v-if="results.habits.length > 0">
                        <p class="px-4 py-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider bg-gray-50 dark:bg-gray-800">
                            Habits
                        </p>
                        <button
                            v-for="(habit, index) in results.habits"
                            :key="'h-' + habit.id"
                            @click="goTo(route('habits.show', habit.id))"
                            @mouseenter="activeIndex = index"
                            :class="[
                                'w-full flex items-center gap-3 px-4 py-3 text-left transition-colors',
                                activeIndex === index
                                    ? 'bg-indigo-50 dark:bg-indigo-950'
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                            ]">
                            <div :class="[
                                'w-8 h-8 rounded-lg flex items-center justify-center shrink-0',
                                activeIndex === index ? 'bg-indigo-100 dark:bg-indigo-900' : 'bg-gray-100 dark:bg-gray-700'
                            ]">
                                <Flame class="w-4 h-4 text-indigo-500" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">
                                    {{ habit.name }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ habit.category?.name ?? 'No category' }} •
                                    <span :class="statusColor(habit.status)">{{ habit.status }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-1 text-orange-500 shrink-0">
                                <Flame class="w-3.5 h-3.5" />
                                <span class="text-xs font-semibold">{{ habit.current_streak }}</span>
                            </div>
                        </button>
                    </div>

                    <!-- Categories -->
                    <div v-if="results.categories.length > 0">
                        <p class="px-4 py-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider bg-gray-50 dark:bg-gray-800">
                            Categories
                        </p>
                        <button
                            v-for="(cat, index) in results.categories"
                            :key="'c-' + cat.id"
                            @click="goTo(route('categories.index'))"
                            @mouseenter="activeIndex = results.habits.length + index"
                            :class="[
                                'w-full flex items-center gap-3 px-4 py-3 text-left transition-colors',
                                activeIndex === results.habits.length + index
                                    ? 'bg-indigo-50 dark:bg-indigo-950'
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                            ]">
                            <div :class="[
                                'w-8 h-8 rounded-lg flex items-center justify-center shrink-0',
                                activeIndex === results.habits.length + index ? 'bg-indigo-100 dark:bg-indigo-900' : 'bg-gray-100 dark:bg-gray-700'
                            ]">
                                <Tag class="w-4 h-4 text-indigo-500" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">
                                    {{ cat.name }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ cat.habits_count }} habits
                                </p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center gap-4 px-4 py-2.5 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center gap-1.5 text-xs text-gray-400">
                    <kbd class="px-1.5 py-0.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs">↑↓</kbd>
                    navigate
                </div>
                <div class="flex items-center gap-1.5 text-xs text-gray-400">
                    <kbd class="px-1.5 py-0.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs">↵</kbd>
                    select
                </div>
                <div class="flex items-center gap-1.5 text-xs text-gray-400">
                    <kbd class="px-1.5 py-0.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs">esc</kbd>
                    close
                </div>
            </div>

        </DialogContent>
    </Dialog>
</template>

<script setup>
import { ref, watch, nextTick, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    Dialog, DialogContent
} from '@/components/ui/dialog'
import { Search, Flame, Tag, Loader2 } from 'lucide-vue-next'

const props = defineProps({ open: Boolean })
const emit  = defineEmits(['update:open'])

const query       = ref('')
const loading     = ref(false)
const activeIndex = ref(0)
const searchInput = ref(null)
const results     = ref({ habits: [], categories: [] })

let debounceTimer = null

// Focus input when modal opens
watch(() => props.open, (val) => {
    if (val) {
        query.value       = ''
        results.value     = { habits: [], categories: [] }
        activeIndex.value = 0
        nextTick(() => searchInput.value?.focus())
    }
})

// Debounced search
watch(query, (val) => {
    clearTimeout(debounceTimer)
    if (!val.trim()) {
        results.value = { habits: [], categories: [] }
        return
    }
    loading.value = true
    debounceTimer = setTimeout(() => doSearch(val), 300)
})

const doSearch = async (q) => {
    try {
        const response = await fetch(route('search', { q }))
        const data     = await response.json()
        results.value  = data
        activeIndex.value = 0
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

// Total results count for keyboard nav
const totalResults = computed(() =>
    results.value.habits.length + results.value.categories.length
)

const moveDown = () => {
    if (activeIndex.value < totalResults.value - 1) activeIndex.value++
}

const moveUp = () => {
    if (activeIndex.value > 0) activeIndex.value--
}

const selectCurrent = () => {
    const i = activeIndex.value
    const habits     = results.value.habits
    const categories = results.value.categories

    if (i < habits.length) {
        goTo(route('habits.show', habits[i].id))
    } else {
        goTo(route('categories.index'))
    }
}

const goTo = (url) => {
    emit('update:open', false)
    router.visit(url)
}

const statusColor = (s) => ({
    active:    'text-green-500',
    inactive:  'text-gray-400',
    completed: 'text-blue-500',
    paused:    'text-yellow-500',
}[s] ?? '')
</script>
