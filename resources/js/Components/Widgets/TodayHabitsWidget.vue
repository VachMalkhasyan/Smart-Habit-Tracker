<template>
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm h-full flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800 shrink-0">
            <h2 class="font-semibold text-gray-800 dark:text-gray-200">Today's Habits</h2>
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">
                    {{ todayCompleted }}/{{ habits.length }} done
                </span>
                <button @click="isReorderMode = !isReorderMode" :class="['text-xs font-semibold px-2 py-1 rounded', isReorderMode ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300']">
                    {{ isReorderMode ? 'Done' : 'Reorder' }}
                </button>
            </div>
        </div>

        <div class="divide-y divide-gray-50 dark:divide-gray-800 overflow-y-auto flex-1 relative">
            <div v-if="habits.length === 0" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                <ListChecks class="w-10 h-10 mx-auto mb-2 opacity-30" />
                <p class="text-sm">No habits yet. Create your first one!</p>
            </div>

            <draggable
                v-model="localHabits"
                item-key="id"
                handle=".habit-drag-handle"
                :animation="200"
                @end="onDragEnd"
            >
                <template #item="{ element: habit }">
                    <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:bg-gray-800 transition-colors group">
                        
                        <!-- Drag Handle -->
                        <div v-show="isReorderMode" class="habit-drag-handle cursor-move text-gray-400 hover:text-indigo-600 transition-colors">
                            <GripVertical class="w-5 h-5" />
                        </div>

                <!-- Check button -->
                <button @click="toggleHabit(habit)"
                        :class="[
                        'w-8 h-8 rounded-full border-2 flex items-center justify-center shrink-0 transition-all',
                        habit.is_done_today
                            ? 'bg-indigo-600 border-indigo-600 text-white'
                            : 'border-gray-300 hover:border-indigo-400 dark:border-gray-600 dark:hover:border-indigo-500'
                    ]">
                    <Check v-if="habit.is_done_today" class="w-4 h-4" />
                </button>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <p :class="['text-sm font-medium truncate', habit.is_done_today ? 'line-through text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-gray-200']">
                        {{ habit.name }}
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                        {{ habit.category?.name ?? 'No category' }} •
                        {{ habit.today_count }}/{{ habit.repeat_count }}x today
                    </p>
                </div>

                <!-- Streak -->
                <div class="flex items-center gap-1 text-orange-500">
                    <Flame class="w-4 h-4" />
                    <span class="text-sm font-semibold">{{ habit.current_streak }}</span>
                </div>

                    <span :class="priorityClass(habit.priority)"
                          class="text-xs px-2 py-0.5 rounded-full font-medium hidden sm:inline-block">
                        {{ priorityLabel(habit.priority) }}
                    </span>
                </div>
                </template>
            </draggable>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import draggable from 'vuedraggable'
import { Check, Flame, ListChecks, GripVertical } from 'lucide-vue-next'

const props = defineProps({
    habits: { type: Array, default: () => [] },
    todayCompleted: Number
})

const isReorderMode = ref(false)
const localHabits = ref([...props.habits])

watch(() => props.habits, (newHabits) => {
    localHabits.value = [...newHabits]
}, { deep: true })

const onDragEnd = async () => {
    try {
        await axios.post('/habits/reorder', {
            habits: localHabits.value.map(h => h.id)
        })
    } catch (e) {
        console.error('Failed to reorder habits', e)
    }
}

const priorityClass = (p) => ({
    1: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    2: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
    3: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
}[p] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400')

const priorityLabel = (p) => ({ 1: 'High', 2: 'Medium', 3: 'Low' }[p] ?? '')

const toggleHabit = (habit) => {
    if (habit.repeat_count > 1 && !habit.is_done_today) {
        router.post(route('completions.increment', habit.id), {}, { preserveScroll: true })
    } else {
        router.post(route('completions.toggle', habit.id), {}, { preserveScroll: true })
    }
}
</script>
