<template>
    <AppLayout title="Categories" subtitle="Manage your habit categories">

        <div class="max-w-3xl mx-auto space-y-6">

            <!-- Create New Category -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Add Custom Category</h2>
                <form @submit.prevent="submit" class="flex gap-3">
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="e.g. Mental Health, Sports..."
                        class="flex-1 px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300"
                        :class="form.errors.name ? 'border-red-400' : 'border-gray-200'"
                    />
                    <Button type="submit"
                            :disabled="form.processing"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 shrink-0">
                        <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                        <Plus v-else class="w-4 h-4" />
                        Add
                    </Button>
                </form>
                <p v-if="form.errors.name" class="text-xs text-red-400 mt-2">{{ form.errors.name }}</p>
            </div>

            <!-- Global Categories -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <Globe class="w-4 h-4 text-indigo-400" />
                    <h2 class="font-semibold text-gray-800">Global Categories</h2>
                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full ml-auto">
                        {{ globalCategories.length }}
                    </span>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="cat in globalCategories" :key="cat.id"
                         class="flex items-center justify-between px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-indigo-50 rounded-xl flex items-center justify-center">
                                <Tag class="w-4 h-4 text-indigo-500" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ cat.name }}</p>
                                <p class="text-xs text-gray-400">{{ cat.habits_count }} habits</p>
                            </div>
                        </div>
                        <span class="text-xs text-indigo-500 bg-indigo-50 px-2.5 py-1 rounded-full">
                            Global
                        </span>
                    </div>
                </div>
            </div>

            <!-- User Custom Categories -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <User class="w-4 h-4 text-green-400" />
                    <h2 class="font-semibold text-gray-800">My Categories</h2>
                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full ml-auto">
                        {{ userCategories.length }}
                    </span>
                </div>

                <div v-if="userCategories.length === 0"
                     class="px-6 py-10 text-center text-gray-400">
                    <Tag class="w-8 h-8 mx-auto mb-2 opacity-30" />
                    <p class="text-sm">No custom categories yet</p>
                </div>

                <div v-else class="divide-y divide-gray-50">
                    <div v-for="cat in userCategories" :key="cat.id"
                         class="flex items-center justify-between px-6 py-4 group">

                        <!-- View mode -->
                        <template v-if="editingId !== cat.id">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-green-50 rounded-xl flex items-center justify-center">
                                    <Tag class="w-4 h-4 text-green-500" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ cat.name }}</p>
                                    <p class="text-xs text-gray-400">{{ cat.habits_count }} habits</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="startEdit(cat)"
                                        class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button @click="confirmDelete(cat)"
                                        class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </template>

                        <!-- Edit mode -->
                        <template v-else>
                            <input v-model="editName" type="text"
                                   class="flex-1 px-3 py-1.5 text-sm border border-indigo-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 mr-3"
                                   @keyup.enter="saveEdit(cat)"
                                   @keyup.escape="editingId = null"
                            />
                            <div class="flex items-center gap-2">
                                <button @click="saveEdit(cat)"
                                        class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600">
                                    <Check class="w-4 h-4" />
                                </button>
                                <button @click="editingId = null"
                                        class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Category</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete <strong>{{ categoryToDelete?.name }}</strong>?
                        Habits using this category will be uncategorized.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteCategory">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription, DialogFooter
} from '@/components/ui/dialog'
import { Plus, Tag, Pencil, Trash2, Check, X, Globe, User, Loader2 } from 'lucide-vue-next'

const props = defineProps({
    categories: Array,
})

// Split global vs user
const globalCategories = computed(() => props.categories.filter(c => c.user_id === null))
const userCategories   = computed(() => props.categories.filter(c => c.user_id !== null))

// Create form
const form = useForm({ name: '' })

const submit = () => {
    form.post(route('categories.store'), {
        onSuccess: () => form.reset()
    })
}

// Inline edit
const editingId = ref(null)
const editName  = ref('')

const startEdit = (cat) => {
    editingId.value = cat.id
    editName.value  = cat.name
}

const saveEdit = (cat) => {
    router.put(route('categories.update', cat.id), { name: editName.value }, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null }
    })
}

// Delete
const deleteDialog      = ref(false)
const categoryToDelete  = ref(null)

const confirmDelete = (cat) => {
    categoryToDelete.value = cat
    deleteDialog.value = true
}

const deleteCategory = () => {
    router.delete(route('categories.destroy', categoryToDelete.value.id), {
        onSuccess: () => { deleteDialog.value = false }
    })
}
</script>
