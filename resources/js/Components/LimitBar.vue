<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: String,
    current: Number,
    limit: Number,
    unit: {
        type: String,
        default: ''
    }
});

const percentage = computed(() => {
    if (props.limit === -1) return 0;
    if (props.limit === 0) return 100;
    return Math.min(Math.round((props.current / props.limit) * 100), 100);
});

const isNearLimit = computed(() => {
    if (props.limit === -1) return false;
    return percentage.value >= 80;
});

const isAtLimit = computed(() => {
    if (props.limit === -1) return false;
    return props.current >= props.limit;
});

const colorClass = computed(() => {
    if (isAtLimit.value) return 'bg-destructive shadow-[0_0_8px_rgba(239,68,68,0.4)]';
    if (isNearLimit.value) return 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.3)]';
    return 'bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.3)]';
});
</script>

<template>
    <div class="space-y-1.5 w-full">
        <div class="flex justify-between text-[11px] font-semibold tracking-tight uppercase">
            <span class="text-muted-foreground">{{ label }}</span>
            <span :class="isAtLimit ? 'text-destructive' : 'text-foreground'">
                {{ current }} / {{ limit === -1 ? '∞' : limit }} {{ unit }}
            </span>
        </div>
        <div class="w-full h-1.5 bg-secondary rounded-full overflow-hidden border border-border/50">
            <div 
                class="h-full transition-all duration-1000 cubic-bezier(0.34, 1.56, 0.64, 1) rounded-full" 
                :class="colorClass"
                :style="{ width: `${percentage}%` }"
            />
        </div>
        <div v-if="isAtLimit && limit !== -1" class="flex items-center gap-1 text-[10px] text-destructive/90 font-medium italic">
            <span>✨ Limit reached. Upgrade for more possibilities.</span>
        </div>
    </div>
</template>
