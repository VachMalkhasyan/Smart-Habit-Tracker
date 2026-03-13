<template>
    <div v-if="xp" class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800">
        <!-- Level badge -->
        <div class="flex items-center gap-1.5 shrink-0">
            <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                Lv.{{ xp.level }}
            </span>
            <span class="text-xs text-gray-400 dark:text-gray-500">
                {{ levelTitle }}
            </span>
        </div>

        <!-- XP bar -->
        <div class="flex-1">
            <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                     :style="{ width: xp.percent + '%' }">
                </div>
            </div>
        </div>

        <!-- XP text -->
        <span class="text-xs text-gray-400 dark:text-gray-500 shrink-0">
            {{ xp.progress_xp }}/{{ xp.needed_xp }} XP
        </span>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    xp: Object,
})

const levelTitle = computed(() => {
    const l = props.xp?.level ?? 1
    if (l >= 50) return '🏆 Legend'
    if (l >= 40) return '💎 Diamond'
    if (l >= 30) return '🥇 Gold'
    if (l >= 20) return '🥈 Silver'
    if (l >= 10) return '🥉 Bronze'
    if (l >= 5)  return '⭐ Rising Star'
    return '🌱 Beginner'
})
</script>
