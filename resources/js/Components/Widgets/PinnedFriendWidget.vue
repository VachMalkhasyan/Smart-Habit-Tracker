<template>
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col h-full">
        <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center shrink-0">
            <h2 class="font-semibold text-gray-800 dark:text-gray-200">👥 Pinned Friend</h2>
            <select v-if="friends.length > 0" v-model="selectedFriendId" @change="saveConfig" class="text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 rounded-lg p-1 pr-8">
                <option value="">Select Friend</option>
                <option v-for="friend in friends" :key="friend.id" :value="friend.id">{{ friend.name }}</option>
            </select>
        </div>
        <div class="p-5 flex-1 overflow-y-auto">
            <div v-if="friends.length === 0" class="text-center text-sm text-gray-400 py-4">
                You have no friends yet.
            </div>
            <div v-else-if="!selectedFriendId" class="text-center text-sm text-gray-500 py-4">
                Please select a friend to pin
            </div>
            <div v-else-if="!pinnedFriend" class="text-center text-sm text-gray-500 py-4">
                Friend not found
            </div>
            <div v-else-if="pinnedFriend.completions.length === 0" class="text-center text-sm text-gray-500 py-4">
                No recent activity
            </div>
            <div v-else class="space-y-4">
                <div v-for="comp in pinnedFriend.completions" :key="comp.id" class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5">
                        ✓
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                            {{ comp.habit?.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ new Date(comp.completed_at).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' }) }}
                        </p>
                    </div>
                    <div v-if="comp.cheers?.length" class="ml-auto flex gap-1 flex-wrap justify-end shrink-0 max-w-[60px]">
                        <span v-for="cheer in comp.cheers" :key="cheer.id" class="text-sm" :title="cheer.user?.name + ' cheered!'">{{ cheer.emoji }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    friends: { type: Array, default: () => [] },
    widgetConfig: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update-config'])

const selectedFriendId = ref(props.widgetConfig?.friendId || '')

const pinnedFriend = computed(() => {
    return props.friends.find(f => f.id === selectedFriendId.value)
})

const saveConfig = () => {
    emit('update-config', { friendId: selectedFriendId.value })
}
</script>
