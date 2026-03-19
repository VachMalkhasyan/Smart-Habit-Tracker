<script setup>
import { useUpgradeStore } from '@/stores/upgradeStore';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Sparkles, Check, Zap, Rocket } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

const upgradeStore = useUpgradeStore();

const plans = [
    {
        id: 'pro',
        name: 'Pro',
        price: '9',
        tagline: 'Level up your productivity',
        features: ['20 Habits', 'AI Suggestions', 'Job Tracker', 'Cover Letter Creator'],
        color: 'text-indigo-400',
        borderColor: 'border-indigo-500/30',
        bgColor: 'bg-indigo-500/5',
        buttonColor: 'bg-indigo-600 hover:bg-indigo-700',
        icon: Zap
    },
    {
        id: 'max',
        name: 'Max',
        price: '19',
        tagline: 'Ultimate power for peak performance',
        features: ['Unlimited Habits', 'Claude 3.5 Sonnet AI', 'ATS Resume Scoring', 'Advanced Analytics'],
        color: 'text-purple-400',
        borderColor: 'border-purple-500/40',
        bgColor: 'bg-purple-500/5',
        buttonColor: 'bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700',
        icon: Rocket,
        highlight: true
    }
];
</script>

<template>
    <Dialog :open="upgradeStore.isOpen" @update:open="upgradeStore.close">
        <DialogContent class="max-w-2xl bg-gray-950 border-gray-800 text-white p-0 overflow-hidden shadow-2xl shadow-indigo-500/10">
            <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
            
            <div class="p-6 sm:p-10">
                <DialogHeader class="mb-8">
                    <div class="flex justify-center mb-6">
                        <div class="p-4 rounded-2xl bg-indigo-500/10 text-indigo-400 ring-1 ring-indigo-500/20 shadow-[0_0_20px_rgba(99,102,241,0.15)]">
                            <Sparkles class="w-10 h-10" />
                        </div>
                    </div>
                    <DialogTitle class="text-3xl font-black text-center tracking-tight text-white">
                        Unlock Premium Experience
                    </DialogTitle>
                    <DialogDescription class="text-center text-gray-400 mt-3 text-lg font-medium leading-relaxed">
                        {{ upgradeStore.message || "You've reached a limit! Upgrade to continue building your success story." }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-10">
                    <div 
                        v-for="plan in plans" 
                        :key="plan.id"
                        :class="[
                            'relative p-7 rounded-2xl border transition-all duration-500 hover:translate-y-[-4px]',
                            plan.borderColor, plan.bgColor,
                            plan.highlight ? 'ring-2 ring-purple-500/40 shadow-[0_10px_40px_rgba(168,85,247,0.1)]' : ''
                        ]"
                    >
                        <Badge v-if="plan.highlight" class="absolute -top-3 left-1/2 -translate-x-1/2 bg-purple-600 hover:bg-purple-600 px-4 py-1 border-none shadow-lg shadow-purple-600/30 uppercase tracking-widest text-[10px] font-bold">Recommended</Badge>
                        
                        <div class="flex items-center gap-3 mb-3">
                            <div :class="['p-2 rounded-lg bg-white/5', plan.color]">
                                <component :is="plan.icon" class="w-5 h-5" />
                            </div>
                            <h3 class="font-extrabold text-2xl tracking-tight">{{ plan.name }}</h3>
                        </div>
                        
                        <p class="text-xs text-gray-400 mb-6 font-medium tracking-tight uppercase">{{ plan.tagline }}</p>
                        
                        <div class="flex items-baseline gap-1.5 mb-8">
                            <span class="text-gray-400 text-lg font-bold block mb-1">$</span>
                            <span class="text-5xl font-black tracking-tighter">{{ plan.price }}</span>
                            <span class="text-gray-500 text-sm font-semibold">/mo</span>
                        </div>

                        <ul class="space-y-4 mb-10 min-h-[140px]">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-3 text-sm text-gray-300 font-medium">
                                <div class="p-0.5 rounded-full bg-green-500/10 text-green-500 mt-0.5 shrink-0">
                                    <Check class="w-3.5 h-3.5" />
                                </div>
                                <span class="leading-tight">{{ feature }}</span>
                            </li>
                        </ul>

                        <Link 
                            href="/pricing" 
                            @click="upgradeStore.close"
                            class="block w-full"
                        >
                            <Button :class="['w-full font-black py-7 text-lg rounded-xl shadow-xl transition-all active:scale-95', plan.buttonColor]">
                                Start with {{ plan.name }}
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <DialogFooter class="px-8 py-6 bg-white/5 border-t border-white/10 flex flex-col items-center gap-4">
                <div class="flex flex-col items-center gap-1">
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Safe & Secure Payment</p>
                    <div class="flex items-center gap-4 opacity-50 grayscale hover:grayscale-0 transition-all cursor-default">
                        <span class="text-[10px] font-black border border-gray-600 px-1.5 py-0.5 rounded text-gray-400">VISA</span>
                        <span class="text-[10px] font-black border border-gray-600 px-1.5 py-0.5 rounded text-gray-400">STRIPE</span>
                    </div>
                </div>
                <button 
                    @click="upgradeStore.close"
                    class="text-xs text-indigo-400 hover:text-indigo-300 font-bold uppercase tracking-widest transition-colors"
                >
                    Dismiss for now
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
