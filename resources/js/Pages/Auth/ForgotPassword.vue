<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../Components/Auth/AuthLayout.vue'

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <AuthLayout>
        <div class="w-full max-w-md mx-auto">
            <div class="flex items-center justify-center gap-2 text-white mb-6">
                <span class="h-2.5 w-2.5 rounded-full bg-[#7c6aff]"></span>
                <span class="text-lg font-extrabold tracking-tight" style="font-family: 'Syne', sans-serif">
                    GrowthZone
                </span>
            </div>

            <div class="rounded-2xl border border-white/10 bg-[#0f0f14] overflow-hidden">
                <div class="h-0.5 w-full bg-gradient-to-r from-[#7c6aff] via-purple-400 to-transparent"></div>

                <div class="p-8">
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-2 text-xs text-white/55 hover:text-white/80 transition-all duration-200"
                        style="font-family: 'DM Sans', sans-serif"
                    >
                        ← Back to login
                    </Link>

                    <div class="mt-6">
                        <div class="text-2xl">🔐</div>
                        <h1 class="mt-3 text-2xl font-extrabold text-white" style="font-family: 'Syne', sans-serif">
                            Reset your password
                        </h1>
                        <p class="mt-2 text-sm text-white/50" style="font-family: 'DM Sans', sans-serif">
                            Enter your email and we'll send a reset link
                        </p>
                    </div>

                    <div v-if="status" class="mt-5 text-sm text-emerald-400/90" style="font-family: 'DM Sans', sans-serif">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="mt-6 space-y-4">
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

                        <button
                            type="submit"
                            class="w-full py-3 px-4 rounded-xl text-sm font-extrabold text-white bg-[#7c6aff] hover:bg-[#6b5bff] transition-all duration-200 disabled:opacity-50"
                            style="font-family: 'Syne', sans-serif"
                            :disabled="form.processing"
                        >
                            Send reset link →
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
