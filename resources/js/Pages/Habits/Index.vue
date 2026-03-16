<template>
    <AppLayout title="My Habits" subtitle="Manage and track all your habits">

        <template #header-actions>
            <div class="flex items-center gap-2">
                <ExportButton />
                <Link :href="route('habits.create')">
                    <Button class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2">
                        <Plus class="w-4 h-4" />
                        New Habit
                    </Button>
                </Link>
            </div>
        </template>

        <!-- Filters Bar -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <div class="relative flex-1 min-w-[200px] max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input v-model="search" type="text" placeholder="Search habits..."
                       class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl bg-white dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600" />
            </div>

            <select v-model="statusFilter"
                    class="text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="completed">Completed</option>
                <option value="paused">Paused</option>
            </select>

            <select v-model="categoryFilter"
                    class="text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600">
                <option value="">All Categories</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>

            <select v-model="priorityFilter"
                    class="text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600">
                <option value="">All Priorities</option>
                <option value="1">High</option>
                <option value="2">Medium</option>
                <option value="3">Low</option>
            </select>
        </div>

        <!-- Results + Drag hint -->
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm text-gray-400 dark:text-gray-500">
                {{ filteredHabits.length }} habits
            </span>
            <div class="flex items-center gap-1.5 text-xs"
                 :class="isFiltered ? 'text-gray-300 dark:text-gray-600' : 'text-gray-400 dark:text-gray-500'">
                <GripVertical class="w-3.5 h-3.5" />
                {{ isFiltered ? 'Clear filters to reorder' : 'Drag to reorder' }}
            </div>
        </div>

        <!-- Habits Grid -->
        <draggable
            v-if="filteredHabits.length > 0"
            v-model="filteredHabits"
            item-key="id"
            :animation="200"
            :disabled="isFiltered"
            ghost-class="drag-ghost"
            chosen-class="drag-chosen"
            drag-class="drag-dragging"
            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"
            @end="onDragEnd"
        >
            <template #item="{ element: habit }">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm hover:shadow-md transition-all group cursor-grab active:cursor-grabbing">

                    <!-- Card Header -->
                    <div class="flex items-start justify-between p-5 pb-3">
                        <div class="flex items-start gap-2 flex-1 min-w-0">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span :class="priorityDot(habit.priority)"></span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ habit.category?.name ?? 'No category' }}
                                    </span>
                                </div>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ habit.name }}
                                </h3>
                                <p v-if="habit.description"
                                   class="text-xs text-gray-400 dark:text-gray-500 mt-1 line-clamp-2">
                                    {{ habit.description }}
                                </p>
                            </div>
                        </div>

                        <!-- Actions dropdown -->
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <MoreVertical class="w-4 h-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-40 dark:bg-gray-900 dark:border-gray-700">
                                <DropdownMenuItem @click="viewHabit(habit)" class="dark:text-gray-300 dark:hover:bg-gray-800">
                                    <Eye class="w-4 h-4 mr-2" /> View
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="editHabit(habit)" class="dark:text-gray-300 dark:hover:bg-gray-800">
                                    <Pencil class="w-4 h-4 mr-2" /> Edit
                                </DropdownMenuItem>
                                <DropdownMenuSeparator class="dark:border-gray-700" />
                                <DropdownMenuItem @click="confirmDelete(habit)"
                                                  class="text-red-500 focus:text-red-500 dark:hover:bg-gray-800">
                                    <Trash2 class="w-4 h-4 mr-2" /> Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <!-- Progress Bar -->
                    <div class="px-5 pb-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-400 dark:text-gray-500">Progress</span>
                            <span class="text-xs font-medium text-indigo-600">{{ progressPercent(habit) }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-500 rounded-full transition-all"
                                 :style="{ width: progressPercent(habit) + '%' }">
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="flex items-center justify-between px-5 py-3 border-t border-gray-50 dark:border-gray-800">
                        <div class="flex items-center gap-1 text-orange-500">
                            <Flame class="w-4 h-4" />
                            <span class="text-sm font-semibold">{{ habit.current_streak }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">streak</span>
                        </div>
                        <span :class="statusClass(habit.status)"
                              class="text-xs px-2.5 py-1 rounded-full font-medium capitalize">
                            {{ habit.status }}
                        </span>
                        <div v-if="habit.deadline_value && habit.deadline_unit" class="flex items-center gap-1 text-gray-400 dark:text-gray-500">
                            <Calendar class="w-3.5 h-3.5" />
                            <span class="text-xs">{{ habit.deadline_value }}{{ habit.deadline_unit[0] }}</span>
                        </div>
                    </div>
                </div>
            </template>
        </draggable>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-950 rounded-2xl flex items-center justify-center mb-4">
                <ListChecks class="w-8 h-8 text-indigo-400" />
            </div>
            <h3 class="text-gray-700 dark:text-gray-300 font-semibold mb-1">No habits found</h3>
            <p class="text-sm text-gray-400 dark:text-gray-500 mb-5">
                {{ isFiltered ? 'Try adjusting your filters' : 'Start by creating your first habit' }}
            </p>
            <Link :href="route('habits.create')">
                <Button class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2">
                    <Plus class="w-4 h-4" /> New Habit
                </Button>
            </Link>
        </div>

        <!-- Delete Confirm Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent class="dark:bg-gray-900">
                <DialogHeader>
                    <DialogTitle class="dark:text-gray-100">Delete Habit</DialogTitle>
                    <DialogDescription class="dark:text-gray-400">
                        Are you sure you want to delete <strong>{{ habitToDelete?.name }}</strong>?
                        This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false"
                            class="dark:border-gray-700 dark:text-gray-300">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="deleteHabit">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import draggable from 'vuedraggable'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent,
    DropdownMenuItem, DropdownMenuSeparator
} from '@/components/ui/dropdown-menu'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription, DialogFooter
} from '@/components/ui/dialog'
import {
    Plus, Search, Flame, Calendar, MoreVertical,
    Eye, Pencil, Trash2, ListChecks, GripVertical
} from 'lucide-vue-next'
import ExportButton from '@/Components/ExportButton.vue'

const props = defineProps({
    habits:     Array,
    categories: Array,
})

// Filters
const search         = ref('')
const statusFilter   = ref('')
const categoryFilter = ref('')
const priorityFilter = ref('')

// Local copy for drag & drop — always full list
const draggableHabits = ref([...(props.habits ?? [])])

// Sync when Inertia reloads props
watch(() => props.habits, (val) => {
    draggableHabits.value = [...(val ?? [])]
})

// Filtered list for display
const filteredHabits = computed({
    get() {
        return draggableHabits.value.filter(h => {
            const matchSearch   = h.name.toLowerCase().includes(search.value.toLowerCase())
            const matchStatus   = !statusFilter.value || h.status === statusFilter.value
            const matchCategory = !categoryFilter.value || h.category_id == categoryFilter.value
            const matchPriority = !priorityFilter.value || h.priority == priorityFilter.value
            return matchSearch && matchStatus && matchCategory && matchPriority
        })
    },
    set(newVal) {
        // When draggable reorders, update draggableHabits preserving non-filtered items
        const filteredIds   = new Set(newVal.map(h => h.id))
        const nonFiltered   = draggableHabits.value.filter(h => !filteredIds.has(h.id))
        draggableHabits.value = [...newVal, ...nonFiltered]
    }
})

// Disable drag when filters active
const isFiltered = computed(() =>
    !!(search.value || statusFilter.value || categoryFilter.value || priorityFilter.value)
)

// On drag end
const onDragEnd = async () => {
    try {
        await axios.post('/habits/reorder', {
            habits: draggableHabits.value.map(h => h.id)
        })
    } catch (e) {
        console.error('Failed to reorder habits', e)
    }
}

// Helpers
const progressPercent = (habit) => {
    if (!habit.goal || !habit.current_streak) return 0
    return Math.min(Math.round((habit.current_streak / habit.goal) * 100), 100)
}

const priorityDot = (p) => ({
    1: 'w-2 h-2 rounded-full bg-red-400 shrink-0',
    2: 'w-2 h-2 rounded-full bg-yellow-400 shrink-0',
    3: 'w-2 h-2 rounded-full bg-green-400 shrink-0',
}[p] ?? 'w-2 h-2 rounded-full bg-gray-300 shrink-0')

const statusClass = (s) => ({
    active:    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    inactive:  'bg-gray-100 text-gray-500 dark:bg-gray-800/50 dark:text-gray-400',
    completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    paused:    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
}[s] ?? '')

// Navigation
const viewHabit = (habit) => router.visit(route('habits.show', habit.id))
const editHabit = (habit) => router.visit(route('habits.edit', habit.id))

// Delete
const deleteDialog  = ref(false)
const habitToDelete = ref(null)

const confirmDelete = (habit) => {
    habitToDelete.value = habit
    deleteDialog.value  = true
}

const deleteHabit = () => {
    router.delete(route('habits.destroy', habitToDelete.value.id), {
        onSuccess: () => { deleteDialog.value = false }
    })
}
</script>

<style scoped>
.drag-ghost {
    opacity: 0.4;
    border: 2px dashed #6366f1 !important;
    background: #eef2ff !important;
    border-radius: 16px;
}

.drag-chosen {
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15) !important;
}

.drag-dragging {
    transform: rotate(1.5deg);
    box-shadow: 0 12px 30px rgba(99, 102, 241, 0.2) !important;
}
</style>
