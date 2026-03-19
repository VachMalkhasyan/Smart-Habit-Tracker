<script setup>
import { useUpgradeStore } from '@/stores/upgradeStore';
import { Button } from '@/components/ui/button';
import { Sparkles } from 'lucide-vue-next';

defineProps({
    feature: String,
    message: String,
    requiredPlan: {
        type: String,
        default: 'pro'
    }
});

const upgradeStore = useUpgradeStore();
</script>

<template>
    <div class="p-4 rounded-xl border border-indigo-500/20 bg-indigo-500/5 flex flex-col sm:flex-row items-center justify-between gap-4 transition-all hover:bg-indigo-500/10">
        <div class="flex items-start gap-3 text-left">
            <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400 shrink-0">
                <Sparkles class="w-5 h-5" />
            </div>
            <div>
                <h4 class="font-semibold text-indigo-100">{{ feature }} Feature</h4>
                <p class="text-sm text-indigo-300/80">{{ message }}</p>
            </div>
        </div>
        <Button 
            @click="upgradeStore.open(feature, requiredPlan, message)"
            class="bg-indigo-600 hover:bg-indigo-700 text-white shrink-0 shadow-lg shadow-indigo-600/20"
        >
            Upgrade Now
        </Button>
    </div>
</template>
