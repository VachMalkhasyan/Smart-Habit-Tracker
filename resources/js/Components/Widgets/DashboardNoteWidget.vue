<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { Check, Edit, Eye } from 'lucide-vue-next';
import { marked } from 'marked';
import { useUnsavedChanges } from '@/composables/useUnsavedChanges';
import UnsavedChangesModal from '@/Components/UnsavedChangesModal.vue';

const props = defineProps({
    initialNote: {
        type: String,
        default: ''
    },
    widgetConfig: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    note: props.initialNote || '',
});

// Track the last-saved value so we can detect unsaved edits
const savedNote = ref(props.initialNote || '');
const isEditing = ref(false);
const showSaved = ref(false);

const MAX_CHARS = 2000;
const charCount = computed(() => form.note.length);

// isDirty: true when editing and content has changed since last save
const isDirty = computed(() => isEditing.value && form.note !== savedNote.value);

const { showModal, confirmLeave, cancelLeave } = useUnsavedChanges(isDirty);

// Configure marked for security and basic features
marked.setOptions({
    gfm: true,
    breaks: true,
});

const renderedNote = computed(() => {
    if (!form.note) return '<p class="text-gray-400 dark:text-gray-500 italic">Click the edit icon to add a note...</p>';
    return marked.parse(form.note);
});

const textareaRef = ref(null);

const startEditing = () => {
    isEditing.value = true;
    setTimeout(() => {
        if (textareaRef.value) {
            textareaRef.value.focus();
        }
    }, 50);
};

const saveNote = () => {
    isEditing.value = false;
    
    axios.patch(route('dashboard.note.update'), {
        note: form.note
    }).then(() => {
        savedNote.value = form.note; // Update saved baseline
        showSaved.value = true;
        setTimeout(() => {
            showSaved.value = false;
        }, 2000);
    }).catch(error => {
        console.error('Failed to save note:', error);
    });
};
</script>

<template>
    <div class="bg-amber-50 dark:bg-gray-900 border border-amber-200 dark:border-amber-900/30 rounded-xl shadow-sm flex flex-col h-full overflow-hidden transition-colors duration-200">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-amber-200/50 dark:border-amber-900/40 flex items-center justify-between bg-amber-100/30 dark:bg-gray-800/30">
            <h3 class="font-semibold text-amber-800 dark:text-amber-500 flex items-center gap-2 text-sm z-10">
                <span>📌</span> Today's Note
            </h3>
            
            <div class="flex items-center gap-3">
                <Transition name="fade">
                    <span v-if="showSaved" class="text-xs text-green-600 dark:text-green-500 flex items-center gap-1 font-medium bg-green-100 dark:bg-green-900/30 px-2 py-0.5 rounded-md">
                        <Check class="w-3 h-3" /> Saved
                    </span>
                </Transition>
                
                <span v-if="isEditing" class="text-xs" :class="charCount > MAX_CHARS ? 'text-red-500' : 'text-amber-600 dark:text-amber-600/70'">
                    {{ charCount }} / {{ MAX_CHARS }}
                </span>

                <button 
                    @click="isEditing ? saveNote() : startEditing()" 
                    class="p-1 rounded-md text-amber-700 hover:bg-amber-200/50 dark:text-amber-500 dark:hover:bg-amber-900/30 transition-colors"
                    :title="isEditing ? 'Preview' : 'Edit'"
                >
                    <Eye v-if="isEditing" class="w-4 h-4" />
                    <Edit v-else class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="flex-grow flex flex-col relative h-[180px] min-h-[180px]">
            <textarea
                v-if="isEditing"
                ref="textareaRef"
                v-model="form.note"
                @blur="saveNote"
                :maxlength="MAX_CHARS"
                class="w-full h-full min-h-[180px] p-4 bg-transparent border-0 focus:ring-0 resize-y text-sm text-gray-800 dark:text-gray-200 placeholder-amber-900/30 dark:placeholder-amber-500/30 font-medium leading-relaxed"
                placeholder="Write your thoughts for today... (Markdown supported)"
            ></textarea>

            <div 
                v-else 
                @click="startEditing"
                class="w-full h-full min-h-[180px] p-4 overflow-y-auto prose prose-sm prose-amber dark:prose-invert max-w-none cursor-text text-gray-800 dark:text-gray-300 [&>*:first-child]:mt-0 [&>*:last-child]:mb-0 prose-p:leading-relaxed prose-a:text-amber-600 dark:prose-a:text-amber-500 hover:prose-a:text-amber-700"
                v-html="renderedNote"
            ></div>
        </div>

        <!-- Unsaved Changes Modal -->
        <UnsavedChangesModal :show="showModal" @confirm="confirmLeave" @cancel="cancelLeave" />
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Custom styling for markdown elements in the prose class */
:deep(.prose pre) {
    background-color: rgba(0, 0, 0, 0.05);
    @apply dark:bg-gray-800;
}
:deep(.prose code) {
    color: #b45309;
    @apply dark:text-amber-400;
}
</style>
