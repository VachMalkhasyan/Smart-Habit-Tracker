<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

const isMobileMenuOpen = ref(false);
const isAnnual = ref(false);
const waitlistSuccess = ref(false);

const form = useForm({
    email: '',
});

const submitWaitlist = () => {
    form.post(route('waitlist.join'), {
        preserveScroll: true,
        onSuccess: () => {
            waitlistSuccess.value = true;
            form.reset('email');
        },
    });
};

// Generate 20 random particles
const particles = Array.from({ length: 20 }, (_, i) => ({
    id: i,
    left: Math.random() * 100 + '%',
    size: Math.random() * 2 + 2 + 'px',
    duration: Math.random() * 12 + 8 + 's',
    delay: Math.random() * 10 + 's',
    opacity: Math.random() * 0.4 + 0.2
}));

onMounted(() => {
    // 1. Scroll fade-in
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

    // 3. Number counter animation
    const animateCounter = (el, target, duration = 2000) => {
        let start = 0;
        const step = target / (duration / 16);
        const timer = setInterval(() => {
            start += step;
            if (start >= target) {
                el.textContent = target + (el.dataset.suffix || '');
                clearInterval(timer);
            } else {
                el.textContent = Math.floor(start) + (el.dataset.suffix || '');
            }
        }, 16);
    };

    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                document.querySelectorAll('.stat-number').forEach(el => {
                    const target = parseInt(el.dataset.target, 10);
                    animateCounter(el, target);
                });
                statsObserver.disconnect();
            }
        });
    }, { threshold: 0.1 });

    const statsSection = document.getElementById('stats');
    if (statsSection) statsObserver.observe(statsSection);

    // 5. Navbar scroll effect
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('navbar');
        if (window.scrollY > 50) {
            nav.style.background = 'rgba(8,8,16,0.92)';
            nav.style.backdropFilter = 'blur(20px)';
            nav.style.borderBottom = '1px solid rgba(255,255,255,0.06)';
        } else {
            nav.style.background = 'transparent';
            nav.style.backdropFilter = 'none';
            nav.style.borderBottom = 'none';
        }
    });
});
</script>

<template>
    <Head title="GrowthZone — Your Personal Growth OS" />
    <div class="bg-[#080810] text-[#a78bfa] min-h-screen font-body selection:bg-[#7c6aff] selection:text-white pb-0 m-0 w-full overflow-x-hidden">
        
        <!-- SECTION 1 — Navbar -->
        <nav id="navbar" class="fixed top-0 w-full z-50 h-16 transition-all duration-300">
            <div class="max-w-7xl mx-auto px-6 h-full flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#7c6aff]"></div>
                    <span class="font-display font-bold text-white text-lg tracking-tight">GrowthZone</span>
                </div>

                <!-- Desktop Links -->
                <div class="hidden md:flex items-center gap-8 text-sm font-medium text-white/70">
                    <a href="#features" class="hover:text-white transition">Features</a>
                    <a href="#pricing" class="hover:text-white transition">Pricing</a>
                    <a href="#mission" class="hover:text-white transition">Mission</a>
                </div>

                <!-- Right Actions -->
                <div class="hidden md:flex items-center gap-4">
                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="text-white/40 hover:text-white transition text-sm">Dashboard</Link>
                    <template v-else>
                        <Link :href="route('login')" class="text-white/40 hover:text-white transition text-sm">Sign in</Link>
                        <Link :href="route('register')" class="bg-[#7c6aff] text-white px-5 py-2 rounded-full text-sm font-medium btn-primary">
                            Get started free &rarr;
                        </Link>
                    </template>
                </div>

                <!-- Mobile menu button -->
                <button class="md:hidden text-white/70 hover:text-white" @click="isMobileMenuOpen = !isMobileMenuOpen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Mobile Menu -->
            <div v-if="isMobileMenuOpen" class="md:hidden absolute top-16 left-0 w-full bg-[#080810]/95 backdrop-blur-xl border-b border-white/10 p-4 flex flex-col gap-4 shadow-2xl transition-all">
                <a href="#features" class="text-white/80 p-2 hover:bg-white/5 rounded" @click="isMobileMenuOpen = false">Features</a>
                <a href="#pricing" class="text-white/80 p-2 hover:bg-white/5 rounded" @click="isMobileMenuOpen = false">Pricing</a>
                <a href="#mission" class="text-white/80 p-2 hover:bg-white/5 rounded" @click="isMobileMenuOpen = false">Mission</a>
                <div class="h-px bg-white/10 my-2"></div>
                <Link :href="route('login')" class="text-white/80 p-2 hover:bg-white/5 rounded text-center">Sign in</Link>
                <Link :href="route('register')" class="bg-[#7c6aff] text-white p-2 rounded-full text-center">Get started free &rarr;</Link>
            </div>
        </nav>

        <!-- SECTION 2 — Hero -->
        <section class="relative min-h-screen flex items-center justify-center pt-20 overflow-hidden hero-bg">
            <!-- Glow effect -->
            <div class="absolute w-[800px] h-[500px] top-[40%] left-1/2 -translate-x-1/2 -translate-y-1/2 bg-[radial-gradient(ellipse_at_center,_rgba(124,106,255,0.08)_0%,_transparent_70%)] pointer-events-none"></div>
            
            <!-- Particles -->
            <div v-for="p in particles" :key="p.id" class="particle" :style="{ left: p.left, width: p.size, height: p.size, animationDuration: p.duration, animationDelay: p.delay, opacity: p.opacity, bottom: '-20px' }"></div>

            <div class="max-w-4xl mx-auto px-6 text-center z-10 fade-in">
                <div class="inline-block border border-[#7c6aff]/30 bg-[#7c6aff]/10 text-[#7c6aff] text-xs px-3 py-1.5 rounded-full mb-6 italic tracking-wide">
                    ✦ Your personal growth operating system
                </div>
                
                <h1 class="font-display font-extrabold text-white text-[40px] md:text-[72px] leading-[1.1] tracking-[-0.02em] mb-6">
                    Build the life <br/>you <span class="font-body italic text-[#7c6aff] font-medium tracking-normal">actually</span> want.
                </h1>
                
                <p class="font-body text-[18px] text-white/50 max-w-[520px] mx-auto mb-10 leading-relaxed">
                    GrowthZone combines AI coaching, habit science, job tracking, and real accountability into one beautiful system.
                </p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                    <Link :href="route('register')" class="w-full sm:w-auto bg-[#7c6aff] text-white px-8 py-3.5 rounded-full font-medium btn-primary flex justify-center">
                        Start for free &rarr;
                    </Link>
                    <a href="#features" class="w-full sm:w-auto border border-white/30 text-white px-8 py-3.5 rounded-full font-medium hover:bg-white/5 transition flex justify-center">
                        See how it works &darr;
                    </a>
                </div>
                
                <div class="flex flex-col items-center gap-3">
                    <div class="flex -space-x-2">
                        <div class="w-8 h-8 rounded-full bg-purple-600 border-2 border-[#080810] flex items-center justify-center text-[10px] text-white font-bold">JD</div>
                        <div class="w-8 h-8 rounded-full bg-teal-500 border-2 border-[#080810] flex items-center justify-center text-[10px] text-white font-bold">AM</div>
                        <div class="w-8 h-8 rounded-full bg-amber-500 border-2 border-[#080810] flex items-center justify-center text-[10px] text-white font-bold">RT</div>
                        <div class="w-8 h-8 rounded-full bg-pink-500 border-2 border-[#080810] flex items-center justify-center text-[10px] text-white font-bold">SK</div>
                        <div class="w-8 h-8 rounded-full bg-blue-500 border-2 border-[#080810] flex items-center justify-center text-[10px] text-white font-bold">LW</div>
                    </div>
                    <p class="text-[13px] text-white/40">Trusted by 2,400+ people growing daily</p>
                </div>
            </div>

            <!-- Scroll indicator -->
            <div class="absolute bottom-10 left-1/2 -translate-x-1/2 animate-bounce flex flex-col items-center">
                <span class="block w-px h-12 bg-gradient-to-b from-white/20 to-transparent"></span>
            </div>
        </section>

        <!-- SECTION 3 — Animated Stats Bar -->
        <section id="stats" class="bg-[#0a0a12] border-y border-white/5 py-12 lg:py-16 fade-in">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-0 divide-x divide-white/5">
                    <div class="text-center">
                        <div class="font-display font-bold text-[48px] text-white tracking-tight stat-number" data-target="201" data-suffix="+">0</div>
                        <div class="text-[13px] text-white/40 mt-1">Habit templates</div>
                    </div>
                    <div class="text-center">
                        <div class="font-display font-bold text-[48px] text-white tracking-tight">AI</div>
                        <div class="text-[13px] text-white/40 mt-1">powered coaching</div>
                    </div>
                    <div class="text-center">
                        <div class="font-display font-bold text-[48px] text-white tracking-tight stat-number" data-target="10" data-suffix="+">0</div>
                        <div class="text-[13px] text-white/40 mt-1">categories</div>
                    </div>
                    <div class="text-center">
                        <div class="font-display font-bold text-[48px] text-white tracking-tight">∞</div>
                        <div class="text-[13px] text-white/40 mt-1">Streaks</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 4 — Features -->
        <section id="features" class="py-24 md:py-32 relative">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16 fade-in">
                    <div class="text-[#7c6aff] text-sm font-medium tracking-wide uppercase mb-3">Everything you need</div>
                    <h2 class="font-display font-bold text-[36px] md:text-[48px] text-white leading-tight">
                        One app. Every tool<br/>for growth.
                    </h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Cards Data -->
                    <template v-for="(feature, index) in [
                        { icon: '🧠', bg: 'bg-purple-500/10', color: 'text-purple-400', title: 'AI Habit Coach', desc: 'Your personal coach that knows your streaks, mood, diary and goals. Powered by Claude AI.' },
                        { icon: '🎯', bg: 'bg-teal-500/10', color: 'text-teal-400', title: 'Habit Tracking', desc: '201+ science-backed templates. Streaks, XP, levels and real accountability built in.' },
                        { icon: '💼', bg: 'bg-amber-500/10', color: 'text-amber-400', title: 'Job Tracker', desc: 'Kanban board for your job search. AI cover letters, ATS scoring, interview prep — all in one place.' },
                        { icon: '😊', bg: 'bg-pink-500/10', color: 'text-pink-400', title: 'Mood Tracking', desc: 'Log daily mood. See how it correlates with your habits. Patterns reveal themselves.' },
                        { icon: '🍅', bg: 'bg-blue-500/10', color: 'text-blue-400', title: 'Pomodoro Timer', desc: 'Stays running in the background. Link sessions to habits. Earn XP for every focused session.' },
                        { icon: '👥', bg: 'bg-green-500/10', color: 'text-green-400', title: 'Friends & Accountability', desc: 'Share progress. Cheer each other. Friend activity feed. Real stakes, real motivation.' },
                        { icon: '📊', bg: 'bg-purple-500/10', color: 'text-purple-400', title: 'Analytics', desc: 'AI-generated weekly summaries. Heatmaps, streaks, completion rates — all beautifully visualized.' },
                        { icon: '🔔', bg: 'bg-teal-500/10', color: 'text-teal-400', title: 'Smart Notifications', desc: 'Streak at risk? Get a personalized AI message — not a generic ping. Reminders at YOUR chosen time.' },
                        { icon: '🔌', bg: 'bg-amber-500/10', color: 'text-amber-400', title: 'Chrome Extension', desc: 'Your habits and Pomodoro timer in every tab. Works offline. Syncs with the app live.' }
                    ]" :key="index">
                        <div class="feature-card fade-in" :style="`transition-delay: ${index * 80}ms`">
                            <div class="bg-white/[0.03] border border-white/[0.06] rounded-[16px] p-7 transition hover:border-[#7c6aff]/30 hover:-translate-y-1 h-full flex flex-col">
                                <div :class="`w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-5 ${feature.bg}`">
                                    {{ feature.icon }}
                                </div>
                                <h3 class="font-display font-semibold text-white text-xl mb-2">{{ feature.title }}</h3>
                                <p class="text-white/50 text-[15px] leading-relaxed">{{ feature.desc }}</p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- SECTION 5 — Mission Statement -->
        <section id="mission" class="py-24 bg-[#050508] border-y border-white/5">
            <div class="max-w-4xl mx-auto px-6 text-center fade-in">
                <div class="text-[#7c6aff] text-sm font-medium tracking-wide uppercase mb-6">Our mission</div>
                <h2 class="font-display font-bold text-[28px] md:text-[42px] text-white leading-[1.3] mb-16 max-w-[800px] mx-auto">
                    "Most apps track your habits.<br/>
                    GrowthZone helps you<br/>
                    <span class="text-[#7c6aff]">understand</span> yourself."
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
                    <div class="border-l border-[#7c6aff] pl-4">
                        <h4 class="font-display font-bold text-[15px] text-white mb-2">Science-backed</h4>
                        <p class="text-[13px] text-white/40 leading-relaxed">Every feature rooted in behavioral science and psychology.</p>
                    </div>
                    <div class="border-l border-[#7c6aff] pl-4">
                        <h4 class="font-display font-bold text-[15px] text-white mb-2">AI that knows you</h4>
                        <p class="text-[13px] text-white/40 leading-relaxed">Not generic advice — advice based on YOUR actual data and life.</p>
                    </div>
                    <div class="border-l border-[#7c6aff] pl-4">
                        <h4 class="font-display font-bold text-[15px] text-white mb-2">Built for humans</h4>
                        <p class="text-[13px] text-white/40 leading-relaxed">No dark patterns. No infinite scroll. Just growth.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 6 — How It Works -->
        <section class="py-24 md:py-32">
            <div class="max-w-7xl mx-auto px-6">
                <h2 class="text-center font-display font-bold text-[36px] text-white mb-20 fade-in">From zero to unstoppable</h2>
                
                <div class="relative grid grid-cols-1 md:grid-cols-3 gap-10">
                    <!-- Dashed line connecting steps (hidden on mobile) -->
                    <div class="hidden md:block absolute top-[28%] left-[16%] right-[16%] h-px border-b-2 border-dashed border-[#7c6aff]/30 z-0"></div>
                    
                    <div class="relative z-10 fade-in bg-[#080810] p-6 rounded-2xl border border-white/5 text-center flex flex-col items-center group hover:border-[#7c6aff]/20 transition">
                        <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] group-hover:opacity-[0.06] transition overflow-hidden rounded-2xl">
                            <span class="font-display font-bold text-[120px] text-white select-none">1</span>
                        </div>
                        <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center text-3xl mb-6 border border-white/10 z-10">🌱</div>
                        <h3 class="font-display font-bold text-white text-xl mb-3 z-10">Set your goals</h3>
                        <p class="text-white/50 text-[14px] leading-relaxed z-10 max-w-xs">Choose habits from 201+ templates or create your own.</p>
                    </div>
                    
                    <div class="relative z-10 fade-in bg-[#080810] p-6 rounded-2xl border border-white/5 text-center flex flex-col items-center group hover:border-[#7c6aff]/20 transition" style="transition-delay: 100ms">
                        <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] group-hover:opacity-[0.06] transition overflow-hidden rounded-2xl">
                            <span class="font-display font-bold text-[120px] text-white select-none">2</span>
                        </div>
                        <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center text-3xl mb-6 border border-white/10 z-10">🤖</div>
                        <h3 class="font-display font-bold text-white text-xl mb-3 z-10">AI learns your patterns</h3>
                        <p class="text-white/50 text-[14px] leading-relaxed z-10 max-w-xs">Every completion, mood log, and diary entry makes your AI coach smarter.</p>
                    </div>
                    
                    <div class="relative z-10 fade-in bg-[#080810] p-6 rounded-2xl border border-white/5 text-center flex flex-col items-center group hover:border-[#7c6aff]/20 transition" style="transition-delay: 200ms">
                        <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] group-hover:opacity-[0.06] transition overflow-hidden rounded-2xl">
                            <span class="font-display font-bold text-[120px] text-white select-none">3</span>
                        </div>
                        <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center text-3xl mb-6 border border-white/10 z-10">🚀</div>
                        <h3 class="font-display font-bold text-white text-xl mb-3 z-10">Watch yourself transform</h3>
                        <p class="text-white/50 text-[14px] leading-relaxed z-10 max-w-xs">Streaks, XP, levels, friends — the system rewards every step forward.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 7 — Pricing -->
        <section id="pricing" class="py-24 bg-[#050508] border-t border-white/5 relative">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-12 fade-in">
                    <h2 class="font-display font-bold text-[36px] text-white mb-3">Simple, honest pricing</h2>
                    <p class="text-white/50 text-[16px]">Start free. Upgrade when you're ready.</p>
                    
                    <!-- Toggle -->
                    <div class="flex items-center justify-center mt-8 space-x-4">
                        <span :class="['text-sm font-medium transition', !isAnnual ? 'text-white' : 'text-white/50']">Monthly</span>
                        <button 
                            @click="isAnnual = !isAnnual"
                            class="relative rounded-full w-14 h-8 transition-colors duration-300 focus:outline-none"
                            :class="isAnnual ? 'bg-[#7c6aff]' : 'bg-white/10'"
                        >
                            <span 
                                class="absolute top-1 bg-white w-6 h-6 rounded-full transition-transform duration-300 transform shadow"
                                :class="isAnnual ? 'translate-x-7 left-0.5' : 'translate-x-1 left-0'"
                            ></span>
                        </button>
                        <span :class="['text-sm font-medium transition flex items-center gap-2', isAnnual ? 'text-white' : 'text-white/50']">
                            Annual 
                            <span class="text-[10px] bg-green-500/20 text-green-400 px-2 py-0.5 rounded-full border border-green-500/30">Save 40%</span>
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Free Card -->
                    <div class="fade-in bg-white/[0.02] border border-white/[0.08] rounded-2xl p-8 relative overflow-hidden h-full flex flex-col">
                        <div class="absolute top-0 left-0 w-full h-1 bg-white/10"></div>
                        <h3 class="font-display font-bold text-2xl text-white mb-2">Free</h3>
                        <div class="flex items-baseline mb-2">
                            <span class="text-4xl font-bold text-white">$0</span>
                            <span class="text-white/40 ml-1">/ month</span>
                        </div>
                        <p class="text-white/50 text-sm mb-8">Forever free</p>
                        
                        <div class="space-y-4 mb-8 flex-grow text-sm">
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">Up to 5 habits</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">Basic streak tracking</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">AI chat (3/day, Groq)</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">Basic Pomodoro timer</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">7-day analytics</span></div>
                            <div class="flex items-center gap-3 opacity-50"><span class="text-red-400">✗</span><span>Friends & accountability</span></div>
                            <div class="flex items-center gap-3 opacity-50"><span class="text-red-400">✗</span><span>Job Tracker</span></div>
                            <div class="flex items-center gap-3 opacity-50"><span class="text-red-400">✗</span><span>Mood correlation</span></div>
                            <div class="flex items-center gap-3 opacity-50"><span class="text-red-400">✗</span><span>Unlimited habits</span></div>
                        </div>
                        
                        <Link :href="route('register')" class="block w-full text-center border border-white/20 hover:bg-white/5 text-white py-3 rounded-xl font-medium transition">
                            Get started free
                        </Link>
                    </div>

                    <!-- Pro Card -->
                    <div class="fade-in bg-[#0a0a16] border-2 border-[#7c6aff] rounded-2xl p-8 relative overflow-hidden h-full flex flex-col shadow-[0_0_40px_rgba(124,106,255,0.15)] transform md:-translate-y-4 z-10" style="transition-delay: 100ms">
                        <div class="absolute top-0 left-0 w-full h-1 bg-[#7c6aff]"></div>
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-display font-bold text-2xl text-white flex items-center gap-2">⭐ Pro</h3>
                            <span class="text-[10px] bg-[#7c6aff]/20 text-[#a78bfa] border border-[#7c6aff]/30 px-2 py-1 rounded uppercase tracking-wider font-bold">Most popular</span>
                        </div>
                        <div class="flex items-baseline mb-2 h-10 transition-all">
                            <span v-if="!isAnnual" class="text-4xl font-bold text-white animate-fade-in">$7<span class="text-sm font-normal text-white/40 ml-1">/ month</span></span>
                            <span v-else class="text-4xl font-bold text-white animate-slide-up">$49<span class="text-sm font-normal text-white/40 ml-1">/ year</span><span class="text-sm font-normal text-white/40 line-through ml-2">$84</span></span>
                        </div>
                        <p class="text-white/50 text-sm mb-8 font-medium">Everything in Free, plus:</p>
                        
                        <div class="space-y-4 mb-8 flex-grow text-sm">
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/90">Unlimited habits</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/90 font-medium text-[#a78bfa]">Claude AI (unlimited)</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/90">Personalized affirmations</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/90">Weekly AI summary</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/90">Full analytics (90 days)</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/90">Friends & accountability</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/90">Job Tracker (20 apps)</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/90">Mood + diary</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/90">Full Chrome Extension</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/90">All 144 templates</span></div>
                        </div>
                        
                        <div>
                            <Link :href="route('register')" class="block w-full text-center bg-[#7c6aff] btn-primary text-white py-3 rounded-xl font-medium transition shadow-lg">
                                Start Pro free trial &rarr;
                            </Link>
                            <p class="text-center text-[#a78bfa] text-xs mt-3">14-day free trial. Cancel anytime.</p>
                        </div>
                    </div>

                    <!-- Max Card -->
                    <div class="fade-in bg-white/[0.02] border border-white/[0.08] rounded-2xl p-8 relative overflow-hidden h-full flex flex-col" style="transition-delay: 200ms">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-200 to-amber-500"></div>
                        <h3 class="font-display font-bold text-2xl text-white mb-2">👑 Max</h3>
                        <div class="flex items-baseline mb-2 h-10 transition-all">
                            <span v-if="!isAnnual" class="text-4xl font-bold text-white animate-fade-in">$14<span class="text-sm font-normal text-white/40 ml-1">/ month</span></span>
                            <span v-else class="text-4xl font-bold text-white animate-slide-up">$99<span class="text-sm font-normal text-white/40 ml-1">/ year</span><span class="text-sm font-normal text-white/40 line-through ml-2">$168</span></span>
                        </div>
                        <p class="text-white/50 text-sm mb-8">Everything in Pro, plus:</p>
                        
                        <div class="space-y-4 mb-8 flex-grow text-sm">
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80 font-medium text-amber-200">Claude Sonnet (smarter AI)</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">CV upload + ATS scoring</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">AI cover letters</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">Unlimited job applications</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">Interview AI prep</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">Company research AI</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">Full history analytics</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">Early access to features</span></div>
                            <div class="flex items-center gap-3"><span class="text-green-400">✓</span><span class="text-white/80">👑 Max badge on profile</span></div>
                        </div>
                        
                        <div>
                            <Link :href="route('register')" class="block w-full text-center bg-white text-black hover:bg-white/90 py-3 rounded-xl font-medium transition shadow-lg">
                                Get Max
                            </Link>
                            <p class="text-center text-[#7c6aff] text-xs mt-3">or $249 lifetime</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 8 — Waitlist / CTA -->
        <section class="py-32 relative bg-[#080810] overflow-hidden border-t border-white/5">
            <!-- Glow -->
            <div class="absolute w-[800px] h-[800px] top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-[radial-gradient(circle,_rgba(124,106,255,0.1)_0%,_transparent_60%)] pointer-events-none"></div>
            
            <div class="max-w-3xl mx-auto px-6 text-center relative z-10 fade-in">
                <h2 class="font-display font-bold text-[40px] md:text-[52px] text-white leading-tight mb-4">Ready to grow?</h2>
                <p class="text-[#a78bfa] text-[18px] mb-10 max-w-xl mx-auto">Join the waitlist and get early access + founding member pricing.</p>
                
                <form v-if="!waitlistSuccess" @submit.prevent="submitWaitlist" class="flex flex-col sm:flex-row max-w-lg mx-auto gap-3">
                    <input 
                        type="email" 
                        v-model="form.email"
                        required
                        placeholder="your@email.com" 
                        class="flex-grow bg-[#1a1a24] border border-white/10 rounded-full px-6 py-4 text-white placeholder-white/40 focus:outline-none focus:border-[#7c6aff] focus:ring-1 focus:ring-[#7c6aff] transition"
                        :disabled="form.processing"
                    />
                    <button 
                        type="submit" 
                        class="bg-[#7c6aff] text-white px-8 py-4 rounded-full font-medium btn-primary whitespace-nowrap"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Joining...' : 'Join the waitlist &rarr;' }}
                    </button>
                </form>
                <div v-else class="max-w-lg mx-auto bg-green-500/10 border border-green-500/30 text-green-400 p-4 rounded-xl flex items-center justify-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="font-medium">You're on the list! We'll be in touch.</span>
                </div>
                
                <p class="text-white/30 text-[13px] mt-6 flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    No spam, ever. Unsubscribe anytime.
                </p>
                <!-- Show validation errors if any -->
                <div v-if="form.errors.email" class="text-red-400 text-sm mt-3">{{ form.errors.email }}</div>
            </div>
        </section>

        <!-- SECTION 9 — Footer -->
        <footer class="bg-[#050508] pt-20 pb-10 border-t border-white/5">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-10 md:gap-8 mb-16">
                    <div class="col-span-2 md:col-span-1">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-3 h-3 rounded-full bg-[#7c6aff]"></div>
                            <span class="font-display font-bold text-white text-xl tracking-tight">GrowthZone</span>
                        </div>
                        <p class="text-white/40 text-sm max-w-[200px]">"Your personal growth OS"</p>
                    </div>
                    
                    <div>
                        <h4 class="text-white font-medium mb-4 text-sm uppercase tracking-wider">Product</h4>
                        <ul class="space-y-3 text-sm text-white/50">
                            <li><a href="#features" class="hover:text-[#7c6aff] transition">Features</a></li>
                            <li><a href="#pricing" class="hover:text-[#7c6aff] transition">Pricing</a></li>
                            <li><a href="#" class="hover:text-[#7c6aff] transition">Templates</a></li>
                            <li><a href="#" class="hover:text-[#7c6aff] transition">Chrome Ext</a></li>
                            <li><a href="#" class="hover:text-[#7c6aff] transition">Download</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="text-white font-medium mb-4 text-sm uppercase tracking-wider">Company</h4>
                        <ul class="space-y-3 text-sm text-white/50">
                            <li><a href="#mission" class="hover:text-[#7c6aff] transition">Mission</a></li>
                            <li><a href="#" class="hover:text-[#7c6aff] transition relative">Blog <span class="text-[9px] bg-white/10 text-white px-1.5 py-0.5 rounded ml-1 uppercase">Soon</span></a></li>
                            <li><a href="#" class="hover:text-[#7c6aff] transition">Contact</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="text-white font-medium mb-4 text-sm uppercase tracking-wider">Legal</h4>
                        <ul class="space-y-3 text-sm text-white/50">
                            <li><a href="#" class="hover:text-[#7c6aff] transition">Privacy Policy</a></li>
                            <li><a href="#" class="hover:text-[#7c6aff] transition">Terms of Service</a></li>
                            <li><a href="#" class="hover:text-[#7c6aff] transition">Cookie Policy</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="border-t border-white/5 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                    <p class="text-white/30 text-sm">© {{ new Date().getFullYear() }} GrowthZone. Built with ❤️ for people who want to grow.</p>
                    <div class="flex items-center gap-4 text-white/30">
                        <a href="#" class="hover:text-white transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                        </a>
                        <a href="#" class="hover:text-white transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                        </a>
                        <a href="#" class="hover:text-white transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd" /></svg>
                        </a>
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>

<style scoped>
.font-display {
    font-family: 'Syne', sans-serif;
}
.font-body {
    font-family: 'DM Sans', sans-serif;
}

/* Animations */
.fade-in {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}
.fade-in.visible {
    opacity: 1;
    transform: translateY(0);
}

.btn-primary {
    transition: all 0.2s ease;
    box-shadow: 0 0 0 0 rgba(124,106,255,0);
}
.btn-primary:hover {
    transform: translateY(-1px) scale(1.02);
    box-shadow: 0 8px 30px rgba(124,106,255,0.35);
}

.hero-bg {
    background: #080810;
    background-image: radial-gradient(circle, rgba(124,106,255,0.15) 1px, transparent 1px);
    background-size: 30px 30px;
}

@keyframes float-up {
    0%   { transform: translateY(0px) translateX(0px); opacity: 0; }
    10%  { opacity: 1; }
    90%  { opacity: 0.6; }
    100% { transform: translateY(-120px) translateX(20px); opacity: 0; }
}

.particle {
    position: absolute;
    border-radius: 50%;
    background: #7c6aff;
    animation: float-up linear infinite;
}

@keyframes fade-in-slide {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in-slide 0.3s ease forwards;
}

.animate-slide-up {
    animation: fade-in-slide 0.3s ease forwards;
}

html {
    scroll-behavior: smooth;
}
</style>
