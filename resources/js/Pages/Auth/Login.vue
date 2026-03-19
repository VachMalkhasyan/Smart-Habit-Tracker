<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Components/Auth/AuthLayout.vue';
import GoogleAuthButton from '@/Components/Auth/GoogleAuthButton.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <AuthLayout>
        <div class="w-full max-w-6xl mx-auto grid md:grid-cols-2 gap-0">
            <!-- Left decorative panel -->
            <div
                class="hidden md:flex relative overflow-hidden rounded-2xl border border-white/5 bg-[#0f0f14] p-10"
            >
                <div
                    class="absolute inset-0 pointer-events-none"
                    style="background: radial-gradient(900px 400px at 20% 30%, rgba(124, 106, 255, 0.18), transparent 60%)"
                ></div>
                <div
                    class="absolute inset-0 pointer-events-none"
                    style="background: linear-gradient(135deg, rgba(124, 106, 255, 0.08), transparent 45%)"
                ></div>

                <div class="relative flex flex-col w-full">
                    <div class="flex items-center gap-2 text-white">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#7c6aff]"></span>
                        <span class="text-lg font-extrabold tracking-tight" style="font-family: 'Syne', sans-serif">
                            GrowthZone
                        </span>
                    </div>

                    <div class="flex-1 flex flex-col justify-center">
                        <h1
                            class="text-4xl font-extrabold leading-tight text-white"
                            style="font-family: 'Syne', sans-serif"
                        >
                            Welcome <span class="text-[#7c6aff]">back</span>
                        </h1>

                        <div class="mt-6 flex gap-2">
                            <span class="px-3 py-1.5 rounded-full text-xs text-white/70 border border-white/10 bg-white/[0.02]">
                                201 templates
                            </span>
                            <span class="px-3 py-1.5 rounded-full text-xs text-white/70 border border-white/10 bg-white/[0.02]">
                                AI powered
                            </span>
                            <span class="px-3 py-1.5 rounded-full text-xs text-white/70 border border-white/10 bg-white/[0.02]">
                                ∞ streaks
                            </span>
                        </div>
                    </div>

                    <div class="mt-10 grid gap-3">
                        <div class="flex items-center gap-3 text-white/70">
                            <div class="h-9 w-9 rounded-xl border border-white/10 bg-white/[0.03] flex items-center justify-center text-white/80">
                                🧠
                            </div>
                            <span class="text-sm" style="font-family: 'DM Sans', sans-serif">AI habit coach</span>
                        </div>
                        <div class="flex items-center gap-3 text-white/70">
                            <div class="h-9 w-9 rounded-xl border border-white/10 bg-white/[0.03] flex items-center justify-center text-white/80">
                                💼
                            </div>
                            <span class="text-sm" style="font-family: 'DM Sans', sans-serif">Job tracker</span>
                        </div>
                        <div class="flex items-center gap-3 text-white/70">
                            <div class="h-9 w-9 rounded-xl border border-white/10 bg-white/[0.03] flex items-center justify-center text-white/80">
                                🍅
                            </div>
                            <span class="text-sm" style="font-family: 'DM Sans', sans-serif">Pomodoro timer</span>
                        </div>
                        <div class="flex items-center gap-3 text-white/70">
                            <div class="h-9 w-9 rounded-xl border border-white/10 bg-white/[0.03] flex items-center justify-center text-white/80">
                                🎯
                            </div>
                            <span class="text-sm" style="font-family: 'DM Sans', sans-serif">XP & levels</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right form panel -->
            <div class="relative rounded-2xl border border-white/10 bg-[#0f0f14] overflow-hidden md:rounded-l-none">
                <div class="h-0.5 w-full bg-gradient-to-r from-[#7c6aff] via-purple-400 to-transparent"></div>

                <div class="p-8 md:p-10">
                    <div class="md:hidden flex items-center justify-center gap-2 text-white mb-8">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#7c6aff]"></span>
                        <span class="text-lg font-extrabold tracking-tight" style="font-family: 'Syne', sans-serif">
                            GrowthZone
                        </span>
                    </div>

                    <h2 class="text-[28px] text-white font-extrabold" style="font-family: 'Syne', sans-serif">
                        Welcome back
                    </h2>
                    <p class="mt-2 text-sm text-white/50" style="font-family: 'DM Sans', sans-serif">
                        Sign in to continue your journey
                    </p>

                    <div v-if="status" class="mt-5 text-sm text-emerald-400/90" style="font-family: 'DM Sans', sans-serif">
                        {{ status }}
                    </div>

                    <div class="mt-6">
                        <GoogleAuthButton />
                    </div>

                    <div class="my-6 flex items-center gap-3">
                        <div class="h-px flex-1 bg-white/10"></div>
                        <div class="text-xs text-white/40" style="font-family: 'DM Sans', sans-serif">or</div>
                        <div class="h-px flex-1 bg-white/10"></div>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Email"
                                class="w-full transition-all duration-200"
                                style="
                                    background: rgba(255, 255, 255, 0.04);
                                    border: 1px solid rgba(255, 255, 255, 0.08);
                                    border-radius: 10px;
                                    padding: 12px 16px;
                                    color: rgba(255, 255, 255, 0.8);
                                    font-family: 'DM Sans', sans-serif;
                                    font-size: 14px;
                                    width: 100%;
                                "
                                @focus="
                                    (e) => {
                                        e.target.style.borderColor = 'rgba(124,106,255,0.6)';
                                        e.target.style.background = 'rgba(124,106,255,0.06)';
                                        e.target.style.outline = 'none';
                                    }
                                "
                                @blur="
                                    (e) => {
                                        e.target.style.borderColor = 'rgba(255,255,255,0.08)';
                                        e.target.style.background = 'rgba(255,255,255,0.04)';
                                    }
                                "
                            />
                            <div v-if="form.errors.email" class="mt-2 text-xs text-red-400" style="font-family: 'DM Sans', sans-serif">
                                {{ form.errors.email }}
                            </div>
                        </div>

                        <div>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="Password"
                                class="w-full transition-all duration-200"
                                style="
                                    background: rgba(255, 255, 255, 0.04);
                                    border: 1px solid rgba(255, 255, 255, 0.08);
                                    border-radius: 10px;
                                    padding: 12px 16px;
                                    color: rgba(255, 255, 255, 0.8);
                                    font-family: 'DM Sans', sans-serif;
                                    font-size: 14px;
                                    width: 100%;
                                "
                                @focus="
                                    (e) => {
                                        e.target.style.borderColor = 'rgba(124,106,255,0.6)';
                                        e.target.style.background = 'rgba(124,106,255,0.06)';
                                        e.target.style.outline = 'none';
                                    }
                                "
                                @blur="
                                    (e) => {
                                        e.target.style.borderColor = 'rgba(255,255,255,0.08)';
                                        e.target.style.background = 'rgba(255,255,255,0.04)';
                                    }
                                "
                            />
                            <div v-if="form.errors.password" class="mt-2 text-xs text-red-400" style="font-family: 'DM Sans', sans-serif">
                                {{ form.errors.password }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2 text-xs text-white/60 select-none" style="font-family: 'DM Sans', sans-serif">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-white/10 bg-white/[0.03] text-[#7c6aff] focus:ring-0"
                                />
                                Remember me
                            </label>

                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs text-[#7c6aff] hover:text-[#9a90ff] transition-all duration-200"
                                style="font-family: 'DM Sans', sans-serif"
                            >
                                Forgot password?
                            </Link>
                        </div>

                        <button
                            type="submit"
                            class="w-full mt-2 py-3 px-4 rounded-xl text-sm font-extrabold text-white bg-[#7c6aff] hover:bg-[#6b5bff] transition-all duration-200 disabled:opacity-50"
                            style="font-family: 'Syne', sans-serif"
                            :disabled="form.processing"
                        >
                            Sign in →
                        </button>
                    </form>

                    <div class="mt-6 text-center text-sm text-white/50" style="font-family: 'DM Sans', sans-serif">
                        Don't have an account?
                        <Link
                            :href="route('register')"
                            class="text-[#7c6aff] hover:text-[#9a90ff] transition-all duration-200"
                        >
                            Create one free
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
