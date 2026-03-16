<template>
    <div v-if="xp" :class="['border-t border-gray-100 dark:border-gray-800 transition-all', 
                           collapsed ? 'px-2 py-3' : 'px-4 py-2.5 bg-white dark:bg-gray-900']">
        
        <!-- Collapsed: show only level badge centered -->
        <div v-if="collapsed" class="flex justify-center">
            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                L{{ xp.level }}
            </span>
        </div>

        <!-- Expanded: full XP bar -->
        <template v-else>
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                    Lv.{{ xp.level }}
                </span>
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ xp.progress_xp }}/{{ xp.needed_xp }} XP
                </span>
            </div>
            <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500
                            rounded-full transition-all duration-500"
                    :style="{ width: (xp.percent ?? 0) + '%' }">
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    xp: Object,
    collapsed: Boolean,
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
