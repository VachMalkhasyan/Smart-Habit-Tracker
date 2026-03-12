<template>
    <AppLayout title="My Habits" subtitle="Manage and track all your habits">

        <template #header-actions>
            <Link :href="route('habits.create')">
                <Button class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2">
                    <Plus class="w-4 h-4" />
                    New Habit
                </Button>
            </Link>
        </template>

        <!-- Filters Bar -->
        <div class="flex flex-wrap items-center gap-3 mb-6">

            <!-- Search -->
            <div class="relative flex-1 min-w-[200px] max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search habits..."
                    class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300"
                />
            </div>

            <!-- Status Filter -->
            <select v-model="statusFilter"
                    class="text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="completed">Completed</option>
                <option value="paused">Paused</option>
            </select>

            <!-- Category Filter -->
            <select v-model="categoryFilter"
                    class="text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">All Categories</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>

            <!-- Priority Filter -->
            <select v-model="priorityFilter"
                    class="text-sm border border-gray-200 rounded-xl px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">All Priorities</option>
                <option value="1">High</option>
                <option value="2">Medium</option>
                <option value="3">Low</option>
            </select>

            <!-- Results count -->
            <span class="text-sm text-gray-400 ml-auto">
                {{ filteredHabits.length }} habits
            </span>
        </div>

        <!-- Habits Grid -->
        <div v-if="filteredHabits.length > 0"
             class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div v-for="habit in filteredHabits" :key="habit.id"
                 class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all group">

                <!-- Card Header -->
                <div class="flex items-start justify-between p-5 pb-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span :class="priorityDot(habit.priority)"></span>
                            <span class="text-xs text-gray-400">{{ habit.category?.name ?? 'No category' }}</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 truncate">{{ habit.name }}</h3>
                        <p v-if="habit.description" class="text-xs text-gray-400 mt-1 line-clamp-2">
                            {{ habit.description }}
                        </p>
                    </div>

                    <!-- Actions dropdown -->
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                <MoreVertical class="w-4 h-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-40">
                            <DropdownMenuItem @click="viewHabit(habit)">
                                <Eye class="w-4 h-4 mr-2" /> View
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="editHabit(habit)">
                                <Pencil class="w-4 h-4 mr-2" /> Edit
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem @click="confirmDelete(habit)"
                                              class="text-red-500 focus:text-red-500">
                                <Trash2 class="w-4 h-4 mr-2" /> Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <!-- Progress Bar -->
                <div class="px-5 pb-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-gray-400">Progress</span>
                        <span class="text-xs font-medium text-indigo-600">{{ progressPercent(habit) }}%</span>
                    </div>
                    <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 rounded-full transition-all"
                             :style="{ width: progressPercent(habit) + '%' }">
                        </div>
                    </div>
                </div>

                <!-- Card Footer -->
                <div class="flex items-center justify-between px-5 py-3 border-t border-gray-50">

                    <!-- Streak -->
                    <div class="flex items-center gap-1 text-orange-500">
                        <Flame class="w-4 h-4" />
                        <span class="text-sm font-semibold">{{ habit.current_streak }}</span>
                        <span class="text-xs text-gray-400">streak</span>
                    </div>

                    <!-- Status badge -->
                    <span :class="statusClass(habit.status)"
                          class="text-xs px-2.5 py-1 rounded-full font-medium capitalize">
                        {{ habit.status }}
                    </span>

                    <!-- Deadline -->
                    <div class="flex items-center gap-1 text-gray-400">
                        <Calendar class="w-3.5 h-3.5" />
                        <span class="text-xs">{{ habit.deadline_value }}{{ habit.deadline_unit[0] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center mb-4">
                <ListChecks class="w-8 h-8 text-indigo-400" />
            </div>
            <h3 class="text-gray-700 font-semibold mb-1">No habits found</h3>
            <p class="text-sm text-gray-400 mb-5">
                {{ search || statusFilter || categoryFilter ? 'Try adjusting your filters' : 'Start by creating your first habit' }}
            </p>
            <Link :href="route('habits.create')">
                <Button class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2">
                    <Plus class="w-4 h-4" /> New Habit
                </Button>
            </Link>
        </div>

        <!-- Delete Confirm Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Habit</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete <strong>{{ habitToDelete?.name }}</strong>?
                        This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteHabit">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
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
    Eye, Pencil, Trash2, ListChecks
} from 'lucide-vue-next'

const props = defineProps({
    habits: Array,
    categories: Array,
})

// Filters
const search = ref('')
const statusFilter = ref('')
const categoryFilter = ref('')
const priorityFilter = ref('')

const filteredHabits = computed(() => {
    return (props.habits ?? []).filter(h => {
        const matchSearch = h.name.toLowerCase().includes(search.value.toLowerCase())
        const matchStatus = !statusFilter.value || h.status === statusFilter.value
        const matchCategory = !categoryFilter.value || h.category_id == categoryFilter.value
        const matchPriority = !priorityFilter.value || h.priority == priorityFilter.value
        return matchSearch && matchStatus && matchCategory && matchPriority
    })
})

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
    active:    'bg-green-100 text-green-700',
    inactive:  'bg-gray-100 text-gray-500',
    completed: 'bg-blue-100 text-blue-700',
    paused:    'bg-yellow-100 text-yellow-700',
}[s] ?? 'bg-gray-100 text-gray-500')

// Navigation
const viewHabit = (habit) => router.visit(route('habits.show', habit.id))
const editHabit = (habit) => router.visit(route('habits.edit', habit.id))

// Delete
const deleteDialog = ref(false)
const habitToDelete = ref(null)

const confirmDelete = (habit) => {
    habitToDelete.value = habit
    deleteDialog.value = true
}

const deleteHabit = () => {
    router.delete(route('habits.destroy', habitToDelete.value.id), {
        onSuccess: () => { deleteDialog.value = false }
    })
}
</script>
