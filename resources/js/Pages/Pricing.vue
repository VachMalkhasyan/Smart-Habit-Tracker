<template>
    <AppLayout title="Pricing Plans" subtitle="Choose the plan that fits your growth journey">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-base font-semibold text-indigo-600 dark:text-indigo-400 tracking-wide uppercase">Pricing</h2>
                <p class="mt-1 text-4xl font-extrabold text-gray-900 dark:text-white sm:text-5xl sm:tracking-tight lg:text-6xl">
                    Invest in your better self.
                </p>
                <p class="max-w-xl mt-5 mx-auto text-xl text-gray-500 dark:text-gray-400">
                    Join 12,000+ users who have transformed their lives with Smart Habit Tracker.
                </p>
                
                <!-- Yearly Toggle -->
                <div class="mt-12 flex justify-center items-center gap-4">
                    <span :class="!isYearly ? 'text-gray-900 dark:text-white font-bold' : 'text-gray-500'" class="text-sm">Monthly</span>
                    <button @click="isYearly = !isYearly" 
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 bg-gray-200 dark:bg-gray-700">
                        <span :class="isYearly ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                    <span :class="isYearly ? 'text-gray-900 dark:text-white font-bold' : 'text-gray-500'" class="text-sm">Yearly <span class="ml-1 text-emerald-500 font-bold text-xs bg-emerald-500/10 px-2 py-0.5 rounded-full">Save 20%</span></span>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div v-for="plan in pricingPlans" :key="plan.id" 
                    :class="[
                        'relative rounded-3xl p-8 border transition-all duration-300 hover:scale-[1.02] flex flex-col',
                        plan.popular ? 'border-indigo-500 shadow-2xl shadow-indigo-500/10 bg-white dark:bg-gray-900 z-10' : 'border-gray-200 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50 shadow-sm'
                    ]">
                    
                    <div v-if="plan.popular" class="absolute -top-4 left-1/2 -translate-x-1/2 bg-indigo-500 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-full shadow-lg">
                        Best Value
                    </div>

                    <div class="mb-8">
                        <div class="flex items-center gap-3 mb-4">
                            <div :class="`p-2.5 rounded-2xl bg-gradient-to-br ${plan.gradient}`">
                                <component :is="plan.icon" class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ plan.name }}</h3>
                        </div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-4xl font-black text-gray-900 dark:text-white">{{ isYearly ? plan.priceYearly : plan.priceMonthly }}</span>
                            <span v-if="plan.priceMonthly !== 'Free'" class="text-gray-500 text-sm font-medium">/{{ isYearly ? 'year' : 'month' }}</span>
                        </div>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ plan.description }}</p>
                    </div>

                    <div class="flex-1 space-y-4 mb-10">
                        <div v-for="feature in plan.features" :key="feature" class="flex items-start gap-3">
                            <Check class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" />
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ feature }}</span>
                        </div>
                    </div>

                    <button 
                        @click="handleSubscribe(plan.id)"
                        :class="[
                            'w-full py-4 px-6 rounded-2xl font-bold transition-all flex items-center justify-center gap-2 group',
                            plan.popular 
                                ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-500/30' 
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-700'
                        ]">
                        {{ plan.id === currentPlan ? 'Your Current Plan' : (plan.id === 'free' ? 'Get Started' : 'Upgrade to ' + plan.name) }}
                        <ArrowRight v-if="plan.id !== currentPlan" class="w-4 h-4 transition-transform group-hover:translate-x-1" />
                    </button>
                </div>
            </div>

            <!-- Detailed Comparison Table -->
            <div class="mt-24">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-12 text-center">Compare features</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="py-6 px-4 text-sm font-bold text-gray-500 uppercase tracking-wider">Feature</th>
                                <th class="py-6 px-4 text-center text-sm font-bold text-indigo-400 uppercase tracking-wider">Free</th>
                                <th class="py-6 px-4 text-center text-sm font-bold text-purple-400 uppercase tracking-wider">Pro</th>
                                <th class="py-6 px-4 text-center text-sm font-bold text-orange-400 uppercase tracking-wider">Max</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                            <tr v-for="row in comparisonRows" :key="row.label" class="hover:bg-gray-50 dark:hover:bg-gray-800/20 transition-colors">
                                <td class="py-4 px-4">
                                    <p class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ row.label }}</p>
                                    <p class="text-[10px] text-gray-400">{{ row.hint }}</p>
                                </td>
                                <td v-for="val in ['free', 'pro', 'max']" :key="val" class="py-4 px-4 text-center">
                                    <div v-if="typeof row[val] === 'boolean'" class="flex justify-center">
                                        <Check v-if="row[val]" class="w-5 h-5 text-emerald-500" />
                                        <X v-else class="w-5 h-5 text-gray-300 dark:text-gray-700" />
                                    </div>
                                    <span v-else class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ row[val] }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Check, X, Shield, Zap, Crown, ArrowRight, Bot, Target, Users } from 'lucide-vue-next'

const page = usePage()
const currentPlan = computed(() => page.props.auth.user.plan || 'free')
const isYearly = ref(false)

const pricingPlans = [
    {
        id: 'free',
        name: 'Free',
        priceMonthly: '$0',
        priceYearly: '$0',
        description: 'Perfect for starting your habit-building journey.',
        gradient: 'from-gray-500 to-gray-700',
        icon: Target,
        features: [
            'Up to 5 active habits',
            'Daily AI coaching (3 msgs/day)',
            'Weekly AI Summary',
            'Basic streak tracking',
            'Job tracker (3 applications)'
        ]
    },
    {
        id: 'pro',
        name: 'Pro',
        popular: true,
        priceMonthly: '$12',
        priceYearly: '$115',
        description: 'Accelerate your growth with advanced AI and networking.',
        gradient: 'from-indigo-600 to-purple-600',
        icon: Zap,
        features: [
            'Up to 15 active habits',
            'Advanced AI Coach (15 msgs/day)',
            'Claude 3.5 Sonnet access',
            'Unlimited Networking & Friends',
            'CV Scorer & Cover Letters',
            'Mood-Habit Correlation'
        ]
    },
    {
        id: 'max',
        name: 'Max',
        priceMonthly: '$29',
        priceYearly: '$278',
        description: 'The ultimate tool for high-performers and teams.',
        gradient: 'from-orange-500 to-red-600',
        icon: Crown,
        features: [
            'Unlimited active habits',
            'Unlimited AI Coaching',
            'Full data history (90 days)',
            'Priority AI & Support',
            'Private Accountability Groups',
            'Custom AI Personas'
        ]
    }
]

const comparisonRows = [
    { label: 'Active Habits', hint: 'Simultaneous goals', free: '5', pro: '15', max: 'Unlimited' },
    { label: 'Daily AI Messages', hint: 'Coaching conversations', free: '3', pro: '15', max: 'Unlimited' },
    { label: 'Advanced AI Models', hint: 'Claude 3.5 Sonnet', free: false, pro: true, max: true },
    { label: 'Data History', hint: 'Analytics depth', free: '7 days', pro: '30 days', max: '90 days' },
    { label: 'Networking & Friends', hint: 'Social accountability', free: false, pro: true, max: true },
    { label: 'CV Scorer & ATS', hint: 'Career acceleration', free: false, pro: true, max: true },
    { label: 'Cover Letter Gen', hint: 'AI-tailored letters', free: false, pro: true, max: true },
    { label: 'Mood Correlation', hint: 'Emotion vs. Productivity', free: false, pro: true, max: true },
    { label: 'Export Data', hint: 'CSV & PDF exports', free: '1/week', pro: 'Unlimited', max: 'Unlimited' },
]

const handleSubscribe = (planId) => {
    if (planId === currentPlan.value) return
    alert(`Stripe integration is coming soon! You selected: ${planId} (${isYearly.value ? 'yearly' : 'monthly'})`)
}
</script>
