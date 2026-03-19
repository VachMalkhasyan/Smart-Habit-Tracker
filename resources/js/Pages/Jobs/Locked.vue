<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { useUpgradeStore } from '@/stores/upgradeStore';
import { Lock, Sparkles, Rocket, Zap, Check } from 'lucide-vue-next';

const props = defineProps({
    feature: String,
    requiredPlan: String,
    message: String,
});

const upgradeStore = useUpgradeStore();

const features = [
    'Job Application Tracking',
    'AI Cover Letter Generation',
    'Company Research Tools',
    'ATS Resume Scoring',
    'Interview Preparation Guides'
];
</script>

<template>
    <AppLayout title="Feature Locked">
        <Head :title="`${feature} Locked`" />

        <div class="min-h-[80vh] flex items-center justify-center p-4">
            <div class="max-w-4xl w-full grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <!-- Left: Visual/Value Prop -->
                <div class="hidden md:block space-y-8 animate-in fade-in slide-in-from-left-8 duration-700">
                    <div class="space-y-4">
                        <h2 class="text-4xl font-black tracking-tight text-white leading-tight">
                            Take Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">Career</span> to the Next Level
                        </h2>
                        <p class="text-gray-400 text-lg">
                            Upgrade to <span class="text-indigo-400 font-bold uppercase tracking-wider text-sm">{{ requiredPlan }}</span> to unlock professional tools designed to help you land your dream job faster.
                        </p>
                    </div>

                    <ul class="space-y-4">
                        <li v-for="f in features" :key="f" class="flex items-center gap-3 text-gray-300 font-medium group">
                            <div class="p-1 rounded-full bg-indigo-500/10 text-indigo-400 group-hover:bg-indigo-500/20 transition-colors">
                                <Check class="w-4 h-4" />
                            </div>
                            <span>{{ f }}</span>
                        </li>
                    </ul>

                    <div class="p-6 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm">
                        <div class="flex items-center gap-4">
                            <div class="flex -space-x-2">
                                <div v-for="i in 3" :key="i" class="w-8 h-8 rounded-full border-2 border-gray-950 bg-gray-800 flex items-center justify-center text-[10px] font-bold">
                                    {{ ['JS', 'AM', 'RL'][i-1] }}
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 font-medium">Joined by 500+ professionals this month</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Locked State Card -->
                <div class="bg-gray-900/50 border border-gray-800 rounded-3xl p-8 sm:p-12 text-center backdrop-blur-md shadow-2xl animate-in zoom-in duration-500">
                    <div class="relative inline-block mb-8">
                        <div class="absolute inset-0 bg-indigo-500 blur-3xl opacity-20"></div>
                        <div class="relative p-6 rounded-3xl bg-gray-950 ring-1 ring-white/10 shadow-2xl">
                            <Lock class="w-12 h-12 text-indigo-500" />
                        </div>
                        <div class="absolute -bottom-2 -right-2 p-2 rounded-xl bg-purple-600 shadow-lg animate-bounce">
                            <Sparkles class="w-4 h-4 text-white" />
                        </div>
                    </div>

                    <h1 class="text-2xl font-bold text-white mb-4">
                        {{ feature }} is Locked
                    </h1>
                    
                    <p class="text-gray-400 mb-10 leading-relaxed max-w-sm mx-auto">
                        {{ message || `The ${feature} feature is part of our professional suite. Upgrade your plan to get full access.` }}
                    </p>

                    <div class="space-y-4">
                        <Button 
                            @click="upgradeStore.open(feature, requiredPlan, message)"
                            size="lg" 
                            class="w-full h-14 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-lg rounded-2xl shadow-xl shadow-indigo-600/20 active:scale-95 transition-all"
                        >
                            <Zap class="w-5 h-5 mr-2" />
                            Upgrade to Unlock
                        </Button>
                        
                        <Link href="/dashboard" class="block w-full">
                            <Button variant="ghost" size="lg" class="w-full text-gray-500 hover:text-white font-bold h-12">
                                Return to Dashboard
                            </Button>
                        </Link>
                    </div>

                    <div class="mt-8 pt-8 border-t border-gray-800/50">
                        <div class="flex items-center justify-center gap-6 opacity-30">
                            <Rocket class="w-6 h-6" />
                            <Zap class="w-6 h-6" />
                            <Sparkles class="w-6 h-6" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.animate-float {
    animation: float 3s ease-in-out infinite;
}
</style>
