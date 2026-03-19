<script setup>
import { computed } from 'vue';
import { usePlan } from '@/composables/usePlan';
import { Badge } from '@/components/ui/badge';
import { Sparkles, Zap, Shield } from 'lucide-vue-next';

const { plan, planName } = usePlan();

const badgeConfig = computed(() => {
    switch (plan.value) {
        case 'pro':
            return {
                class: 'bg-indigo-600 hover:bg-indigo-700 text-white border-none',
                icon: Zap
            };
        case 'max':
            return {
                class: 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white border-none shadow-sm',
                icon: Sparkles
            };
        default:
            return {
                class: 'text-muted-foreground border-muted-foreground/30',
                icon: Shield
            };
    }
});
</script>

<template>
    <Badge variant="outline" :class="['gap-1 capitalize py-0.5 px-2 font-medium', badgeConfig.class]">
        <component :is="badgeConfig.icon" class="w-3 h-3" v-if="badgeConfig.icon" />
        {{ planName }}
    </Badge>
</template>
