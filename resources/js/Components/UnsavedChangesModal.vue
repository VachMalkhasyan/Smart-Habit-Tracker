<script setup>
defineProps({
    show: Boolean,
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="show" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="emit('cancel')"></div>

                <!-- Modal -->
                <Transition name="modal-pop">
                    <div v-if="show"
                        class="relative z-10 bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 w-full max-w-md p-6"
                    >
                        <!-- Icon -->
                        <div class="flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-900/30 mx-auto mb-4">
                            <span class="text-3xl">⚠️</span>
                        </div>

                        <!-- Text -->
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">Unsaved Changes</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6 leading-relaxed">
                            You have unsaved changes that will be lost.<br>What would you like to do?
                        </p>

                        <!-- Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button
                                @click="emit('cancel')"
                                class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            >
                                Keep Editing
                            </button>
                            <button
                                @click="emit('confirm')"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-semibold transition-colors"
                            >
                                Discard &amp; Leave
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to       { opacity: 0; }

.modal-pop-enter-active  { transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-pop-leave-active  { transition: all 0.15s ease; }
.modal-pop-enter-from    { opacity: 0; transform: scale(0.9) translateY(10px); }
.modal-pop-leave-to      { opacity: 0; transform: scale(0.95); }
</style>
